<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

#[\EtruscanNode('source-freshness')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('check')]
final readonly class SourceFreshness
{
    public function __construct(private ReferenceIndex $referenceIndex, private SourceInventory $sourceInventory) {}

    /**
     * @param  array<string, ParsedNote>|null  $notesByAlias  The generated notes when the caller has just read
     *                                                        them, so the structure hash reuses that read.
     */
    public function __invoke(string $vaultPath, ?array $notesByAlias = null): string
    {
        $index = $this->referenceIndex->read($vaultPath);

        if ($index === null) {
            return 'unknown';
        }

        $roots = EtruscanConfig::scannedFolders();
        sort($roots);

        $structure = $notesByAlias === null
            ? $this->referenceIndex->structureHash($vaultPath)
            : ReferenceIndex::structureHashOf($notesByAlias);

        if ($index['roots'] !== $roots
            || $index['marker'] !== [EtruscanConfig::markerKey(), EtruscanConfig::markerValue()]
            || $index['hashes'] !== ($this->sourceInventory)($roots)
            || $index['structure'] !== $structure) {
            return 'stale';
        }

        return $index['issues'] === [] ? 'current' : 'incomplete';
    }

    /**
     * Retained orphan notes are reported beside the status, not folded into it:
     * a note kept for its human words says nothing about whether the scanned
     * evidence is complete.
     */
    public static function describe(string $status, int $orphans = 0): string
    {
        $notice = 'Map freshness: '.$status.'. '.match ($status) {
            'current' => 'Scanned PHP contents match generation; dynamic behavior and human claims still need source verification.',
            'incomplete' => 'Generation reported missing scan roots; run etruscan:check and resolve the diagnostics.',
            'stale' => 'Source or generated structure changed; regenerate before relying on references. Review affected human descriptions.',
            default => 'Local index missing or invalid; run php artisan etruscan:generate (safe on a fresh checkout).',
        };

        if ($orphans > 0) {
            $notice .= sprintf(
                ' %d generated note(s) kept for their human words have no annotated class; review them with etruscan:check.',
                $orphans,
            );
        }

        return $notice;
    }
}
