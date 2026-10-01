<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Services;

use WellDigit\Etruscan\Enums\TraceDirection;
use WellDigit\Etruscan\Services\ReferenceIndex;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

#[\EtruscanNode('source-reference-trace')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('mcp')]
final readonly class SourceReferenceTrace
{
    public function __construct(private ReferenceIndex $referenceIndex) {}

    public function __invoke(string $fqcn, TraceDirection $direction, int $offset, int $limit, string $freshness): string
    {
        $vaultPath = EtruscanConfig::vaultPath();
        $lookup = $this->referenceIndex->lookup($vaultPath);

        if ($lookup === null || $freshness !== 'current') {
            return 'Source evidence unavailable ('.$freshness.'). Regenerate after resolving scan diagnostics; the note links cover annotated nodes only.';
        }

        $key = strtolower($fqcn);
        $matches = [
            ...($direction !== TraceDirection::Out ? $lookup['byTarget'][$key] ?? [] : []),
            ...($direction !== TraceDirection::In ? $lookup['byCaller'][$key] ?? [] : []),
        ];

        if ($direction === TraceDirection::Both) {
            // Each side is already in index order; interleave them back into it.
            usort($matches, static fn (array $a, array $b): int => [$a['source'], $a['line'], $a['target']] <=> [$b['source'], $b['line'], $b['target']]);
        }

        $page = array_slice($matches, $offset, $limit);
        $lines = [sprintf('Source evidence: showing %d of %d occurrences, offset %d (includes unannotated classes).', count($page), count($matches), $offset)];

        foreach ($page as $row) {
            $lines[] = sprintf(
                '- %s%s -> %s [%s] at %s:%d',
                $row['caller'],
                $row['alias'] === null ? ' (unannotated)' : ' ['.$row['alias'].']',
                $row['target'],
                $row['kind'],
                $row['source'],
                $row['line'],
            );
        }

        if ($offset + count($page) < count($matches)) {
            $lines[] = 'More evidence: call trace-node with the same alias and direction, evidence_offset='.($offset + count($page)).'.';
        }

        $lines[] = 'Scope: named classes in configured PHP roots; imports alone are excluded. Dynamic bindings, event wiring, anonymous classes and non-class entry points are not resolved. No matches does not prove no impact.';

        return implode("\n", $lines);
    }
}
