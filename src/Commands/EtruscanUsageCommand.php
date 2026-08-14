<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Payloads\UsageReport;
use WellDigit\Etruscan\Services\UsageLogReader;
use WellDigit\Etruscan\Services\UsagePageRenderer;
use WellDigit\Etruscan\Services\UsageReportBuilder;
use WellDigit\Etruscan\Utilities\AbsolutePathResolver;
use WellDigit\Etruscan\Utilities\EtruscanConfig;
use WellDigit\Etruscan\Utilities\ReportsDirectoryPreparer;
use WellDigit\Etruscan\Utilities\ReportsPathResolver;
use WellDigit\Etruscan\Utilities\TokenEstimator;
use WellDigit\Etruscan\Utilities\UsageLogPathResolver;

#[Description('Report how AI agents actually use the map: consultations, hot nodes and misses')]
#[Signature('etruscan:usage
        {--days= : Only include events recorded in the last N days}
        {--json : Emit the report as JSON}
        {--html : Render the report as a self-contained dashboard page}
        {--output= : Where to write the dashboard (default: .reports/usage.html inside the vault)}')]
#[EtruscanNode('etruscan-usage')]
#[EtruscanLayer('command')]
#[EtruscanContext('usage')]
#[EtruscanContext('cli')]
final class EtruscanUsageCommand extends Command
{
    public function handle(
        UsageLogReader $usageLogReader,
        UsageReportBuilder $usageReportBuilder,
        UsagePageRenderer $usagePageRenderer,
    ): int {
        $daysOption = $this->option('days');
        $windowDays = null;

        if ($daysOption !== null) {
            if (! is_numeric($daysOption) || (int) $daysOption < 1) {
                $this->error('The --days option requires a positive number of days.');

                return self::FAILURE;
            }

            $windowDays = (int) $daysOption;
        }

        $vaultPath = EtruscanConfig::vaultPath();
        $log = $usageLogReader(UsageLogPathResolver::resolve($vaultPath));

        $report = $usageReportBuilder(
            events: $log['events'],
            malformedLines: $log['malformed'],
            newerSchemaLines: $log['newerSchema'],
            sinceTimestamp: $windowDays === null ? null : now()->subDays($windowDays)->getTimestamp(),
            windowDays: $windowDays,
        );

        if ($this->option('json')) {
            $this->line((string) json_encode($this->toArray($report), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        if ($this->option('html')) {
            $outputOption = $this->option('output');
            $customOutput = is_string($outputOption) && $outputOption !== '';
            $outputPath = AbsolutePathResolver::resolve(
                $customOutput ? $outputOption : ReportsPathResolver::resolve($vaultPath, 'usage.html'),
            );

            if ($customOutput) {
                File::ensureDirectoryExists(dirname($outputPath));
            } else {
                ReportsDirectoryPreparer::prepare(dirname($outputPath));
            }

            File::put($outputPath, $usagePageRenderer($report));

            $this->info(sprintf('Rendered the usage dashboard (%d event(s)) into %s', $report->events, $outputPath));

            return self::SUCCESS;
        }

        return $this->render($report);
    }

    private function render(UsageReport $usageReport): int
    {
        if ($usageReport->events === 0) {
            $this->info('No usage recorded yet — agents have not consulted the map through the MCP tools'.($usageReport->windowDays !== null ? sprintf(' in the last %d day(s)', $usageReport->windowDays) : '').'.');

            return self::SUCCESS;
        }

        $window = $usageReport->windowDays !== null ? sprintf(' (last %d day(s))', $usageReport->windowDays) : '';

        $this->info(sprintf('Map usage%s: %d consultation(s), %d hit(s), %d miss(es)', $window, $usageReport->events, $usageReport->hits, $usageReport->misses));

        foreach ($usageReport->byType as $type => $count) {
            $this->line(sprintf('  %-9s %d', $type, $count));
        }

        if ($usageReport->charsServed > 0) {
            $this->line(sprintf(
                '  Context served: %s chars (~%s tokens, estimated at %d chars/token)',
                number_format($usageReport->charsServed),
                number_format(TokenEstimator::estimate($usageReport->charsServed)),
                TokenEstimator::CHARS_PER_TOKEN,
            ));
        }

        if ($usageReport->topNodes !== []) {
            $this->newLine();
            $this->info(sprintf('Most consulted nodes (%d distinct):', $usageReport->distinctNodesConsulted));

            foreach ($usageReport->topNodes as $alias => $count) {
                $this->line(sprintf('  %-40s %d', $alias, $count));
            }
        }

        if ($usageReport->missedSubjects !== []) {
            $this->newLine();
            $this->warn('Misses — the map could not answer these (annotation candidates):');

            foreach ($usageReport->missedSubjects as $subject => $count) {
                $this->line(sprintf('  %-40s %d', $subject, $count));
            }

            $this->line('  Feed these to the etruscan-annotate skill, then regenerate.');
        }

        if ($usageReport->malformedLines > 0 || $usageReport->newerSchemaLines > 0) {
            $this->newLine();
            $this->warn(sprintf('Skipped log lines: %d malformed, %d from a newer schema.', $usageReport->malformedLines, $usageReport->newerSchemaLines));
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(UsageReport $usageReport): array
    {
        return [
            'window_days' => $usageReport->windowDays,
            'events' => $usageReport->events,
            'by_type' => $usageReport->byType,
            'by_day' => $usageReport->byDay,
            'hits' => $usageReport->hits,
            'misses' => $usageReport->misses,
            'top_nodes' => $usageReport->topNodes,
            'missed_subjects' => $usageReport->missedSubjects,
            'empty_searches' => $usageReport->emptySearches,
            'chars_served' => $usageReport->charsServed,
            'estimated_tokens_served' => TokenEstimator::estimate($usageReport->charsServed),
            'distinct_nodes_consulted' => $usageReport->distinctNodesConsulted,
            'skipped' => [
                'malformed' => $usageReport->malformedLines,
                'newer_schema' => $usageReport->newerSchemaLines,
            ],
        ];
    }
}
