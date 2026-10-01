<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Enums\CheckSeverity;
use WellDigit\Etruscan\Payloads\CheckFinding;
use WellDigit\Etruscan\Services\BrokenLinkChecker;
use WellDigit\Etruscan\Services\CodebaseScanner;
use WellDigit\Etruscan\Services\DigestStalenessChecker;
use WellDigit\Etruscan\Services\DuplicateAliasChecker;
use WellDigit\Etruscan\Services\NodeGraphBuilder;
use WellDigit\Etruscan\Services\StructuralMapChecker;
use WellDigit\Etruscan\Services\UnresolvedAttributeChecker;
use WellDigit\Etruscan\Services\VocabularyChecker;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

/**
 * Audits the map for the mistakes annotations invite: duplicate aliases,
 * off-vocabulary axis values, dangling wikilinks, and an exported index that
 * has fallen behind the notes it indexes.
 */
#[Description('Check source-to-map drift, scan diagnostics, vocabulary, links and exported content')]
#[Signature('etruscan:check {--strict : Fail on warnings too, not just errors} {--fresh : Require generated structure to match current source, including a missing vault}')]
#[\EtruscanNode('etruscan-check')]
#[\EtruscanLayer('command')]
#[\EtruscanContext('check')]
#[\EtruscanContext('cli')]
final class EtruscanCheckCommand extends Command
{
    public function handle(
        CodebaseScanner $codebaseScanner,
        NodeGraphBuilder $nodeGraphBuilder,
        DuplicateAliasChecker $duplicateAliasChecker,
        VocabularyChecker $vocabularyChecker,
        BrokenLinkChecker $brokenLinkChecker,
        DigestStalenessChecker $digestStalenessChecker,
        StructuralMapChecker $structuralMapChecker,
        UnresolvedAttributeChecker $unresolvedAttributeChecker,
    ): int {
        $scannedFolders = EtruscanConfig::scannedFolders();
        $vaultPath = EtruscanConfig::vaultPath();

        $vocabulary = EtruscanConfig::vocabulary();

        $this->info('Checking the map ...');

        $scannedClasses = $codebaseScanner($scannedFolders);

        $findings = [
            ...$duplicateAliasChecker($scannedClasses),
            ...$unresolvedAttributeChecker($scannedClasses),
            ...$vocabularyChecker($scannedClasses, $vocabulary),
            ...$digestStalenessChecker($vaultPath),
        ];

        foreach ($codebaseScanner->issues as $issue) {
            $findings[] = new CheckFinding(
                CheckCategory::ScanIncomplete,
                str_starts_with($issue, 'Unparseable file:') ? CheckSeverity::Error : CheckSeverity::Warning,
                $issue,
            );
        }

        if ($this->hasDuplicateAlias($findings)) {
            $this->warn('Skipping the broken-link check until the duplicate aliases above are resolved.');
        } else {
            $notes = $nodeGraphBuilder($scannedClasses);

            $findings = [
                ...$findings,
                ...((is_dir($vaultPath) || $this->option('fresh')) ? $structuralMapChecker($vaultPath, $notes) : []),
                ...$brokenLinkChecker($vaultPath, $notes),
            ];
        }

        return $this->report(findings: $findings, strict: (bool) $this->option('strict'));
    }

    /**
     * @param  list<CheckFinding>  $findings
     */
    private function hasDuplicateAlias(array $findings): bool
    {
        return array_any($findings, static fn (CheckFinding $finding): bool => $finding->category === CheckCategory::DuplicateAlias);
    }

    /**
     * @param  list<CheckFinding>  $findings
     */
    private function report(array $findings, bool $strict): int
    {
        if ($findings === []) {
            $this->info('No problems found — the map is clean.');

            return self::SUCCESS;
        }

        $errors = array_values(array_filter($findings, static fn (CheckFinding $finding): bool => $finding->severity === CheckSeverity::Error));
        $warnings = array_values(array_filter($findings, static fn (CheckFinding $finding): bool => $finding->severity === CheckSeverity::Warning));

        foreach ($errors as $error) {
            $this->error('  '.$error->message);
        }

        foreach ($warnings as $warning) {
            $this->warn('  '.$warning->message);
        }

        $this->line(sprintf('%sFound %d error(s) and %d warning(s).', PHP_EOL, count($errors), count($warnings)));

        return $errors !== [] || ($strict && $warnings !== []) ? self::FAILURE : self::SUCCESS;
    }
}
