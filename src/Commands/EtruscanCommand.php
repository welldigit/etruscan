<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Exceptions\AliasCollisionException;
use WellDigit\Etruscan\Exceptions\InvalidGroupingValueException;
use WellDigit\Etruscan\Exceptions\ReservedAxisKeyException;
use WellDigit\Etruscan\Payloads\NoteContent;
use WellDigit\Etruscan\Services\CodebaseScanner;
use WellDigit\Etruscan\Services\NodeGraphBuilder;
use WellDigit\Etruscan\Services\NotePathResolver;
use WellDigit\Etruscan\Services\VaultWriter;
use WellDigit\Etruscan\Utilities\AxisKeyParser;
use WellDigit\Etruscan\Utilities\EtruscanConfig;
use WellDigit\Etruscan\Utilities\TargetFolderCounter;

#[Description('Project attributed classes into a markdown vault (one note per node)')]
#[Signature('etruscan:generate
        {--vault= : Override the output vault directory}
        {--group-by= : Override the grouping axis key(s), comma-separated for nested folders (use "none" to force a flat vault)}
        {--purge : Delete orphaned generated notes even when they carry human words (a description or manual notes)}
        {--dry-run : Report what would be generated without writing}')]
#[EtruscanNode('etruscan-generate')]
#[EtruscanLayer('command')]
#[EtruscanContext('cli')]
final class EtruscanCommand extends Command
{
    public function handle(
        CodebaseScanner $codebaseScanner,
        NodeGraphBuilder $nodeGraphBuilder,
        VaultWriter $vaultWriter,
    ): int {
        $scannedFolders = EtruscanConfig::scannedFolders();

        $vaultOption = $this->option('vault');
        $vaultPath = EtruscanConfig::vaultPath(is_string($vaultOption) ? $vaultOption : null);

        $groupByOption = $this->option('group-by');

        if ($this->input->hasParameterOption('--group-by')
            && (! is_string($groupByOption) || ($groupByOption !== 'none' && AxisKeyParser::parse($groupByOption) === []))) {
            $this->error('The --group-by option requires an axis key or a comma-separated list, e.g. --group-by=context or --group-by=layer,domain (use --group-by=none for a flat vault).');

            return self::FAILURE;
        }

        $groupByAxisKeys = $this->resolveGroupByAxisKeys();
        $notePathResolver = new NotePathResolver(groupByAxisKeys: $groupByAxisKeys);

        $markerKey = EtruscanConfig::markerKey();
        $markerValue = EtruscanConfig::markerValue();

        $this->info('Scanning for attributed classes ...');

        $scannedClasses = $codebaseScanner($scannedFolders);

        try {
            $notes = $nodeGraphBuilder($scannedClasses);
        } catch (AliasCollisionException|ReservedAxisKeyException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($groupByAxisKeys as $groupByAxisKey) {
            if (! $this->groupingAxisIsPresent(groupByAxisKey: $groupByAxisKey, notes: $notes)) {
                $this->warn(sprintf('No scanned node carries the [%s] axis; that grouping level is skipped.', $groupByAxisKey));
            }
        }

        try {
            if ($this->option('dry-run')) {
                $this->info($groupByAxisKeys === []
                    ? sprintf('Would project %d node(s) into %s', count($notes), $vaultPath)
                    : sprintf(
                        'Would project %d node(s) into %s (%d folder(s), grouped by %s)',
                        count($notes),
                        $vaultPath,
                        TargetFolderCounter::count(notePathResolver: $notePathResolver, notes: $notes),
                        implode(', ', $groupByAxisKeys),
                    ));

                return self::SUCCESS;
            }

            $summary = $vaultWriter(
                vaultPath: $vaultPath,
                notes: $notes,
                notePathResolver: $notePathResolver,
                markerKey: $markerKey,
                markerValue: $markerValue,
                purgeOrphans: (bool) $this->option('purge'),
            );
        } catch (InvalidGroupingValueException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Projected %d node(s) into %s: %d written, %d stale note(s) removed',
            count($notes),
            $vaultPath,
            $summary['written'],
            $summary['removed'],
        ));

        if ($summary['orphaned'] !== []) {
            $this->warn(sprintf(
                'Kept %d orphaned generated note(s) carrying human words (re-run with --purge to delete them, words and all):',
                count($summary['orphaned']),
            ));

            foreach ($summary['orphaned'] as $orphanedPath) {
                $this->line('  - '.$orphanedPath);
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function resolveGroupByAxisKeys(): array
    {
        $groupByOption = $this->option('group-by');

        if (is_string($groupByOption) && $groupByOption !== '') {
            return $groupByOption === 'none' ? [] : AxisKeyParser::parse($groupByOption);
        }

        $configuredGroupBy = EtruscanConfig::groupBy();

        return $configuredGroupBy === null ? [] : AxisKeyParser::parse($configuredGroupBy);
    }

    /**
     * @param  list<NoteContent>  $notes
     */
    private function groupingAxisIsPresent(string $groupByAxisKey, array $notes): bool
    {
        return array_any($notes, fn ($noteContent) => array_key_exists($groupByAxisKey, $noteContent->frontmatter));
    }
}
