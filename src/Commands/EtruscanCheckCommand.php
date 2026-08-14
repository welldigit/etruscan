<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Enums\CheckSeverity;
use WellDigit\Etruscan\Payloads\CheckFinding;
use WellDigit\Etruscan\Services\BrokenLinkChecker;
use WellDigit\Etruscan\Services\CodebaseScanner;
use WellDigit\Etruscan\Services\DuplicateAliasChecker;
use WellDigit\Etruscan\Services\NodeGraphBuilder;
use WellDigit\Etruscan\Services\OrphanNodeChecker;
use WellDigit\Etruscan\Services\VocabularyChecker;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

/**
 * Audits the map for the mistakes annotations invite: duplicate aliases,
 * off-vocabulary axis values, isolated nodes, and dangling wikilinks.
 */
#[Description('Check the map for duplicate aliases, unknown vocabulary, orphans and broken links')]
#[Signature('etruscan:check {--strict : Fail on warnings too, not just errors}')]
#[EtruscanNode('etruscan-check')]
#[EtruscanLayer('command')]
#[EtruscanContext('check')]
#[EtruscanContext('cli')]
final class EtruscanCheckCommand extends Command
{
    public function handle(
        CodebaseScanner $codebaseScanner,
        NodeGraphBuilder $nodeGraphBuilder,
        DuplicateAliasChecker $duplicateAliasChecker,
        VocabularyChecker $vocabularyChecker,
        OrphanNodeChecker $orphanNodeChecker,
        BrokenLinkChecker $brokenLinkChecker,
    ): int {
        $scannedFolders = EtruscanConfig::scannedFolders();
        $vaultPath = EtruscanConfig::vaultPath();

        $vocabulary = EtruscanConfig::vocabulary();

        $this->info('Checking the map ...');

        $scannedClasses = $codebaseScanner($scannedFolders);

        $findings = [
            ...$duplicateAliasChecker($scannedClasses),
            ...$vocabularyChecker($scannedClasses, $vocabulary),
        ];

        if ($this->hasDuplicateAlias($findings)) {
            $this->warn('Skipping orphan and broken-link checks until the duplicate aliases above are resolved.');
        } else {
            $notes = $nodeGraphBuilder($scannedClasses);

            $findings = [
                ...$findings,
                ...$orphanNodeChecker($notes),
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
