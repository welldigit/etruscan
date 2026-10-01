<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Services;

use WellDigit\Etruscan\Enums\TraceDirection;
use WellDigit\Etruscan\Payloads\ParsedNote;

#[\EtruscanNode('node-trace')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('mcp')]
final readonly class NodeTrace
{
    /**
     * @param  array<string, ParsedNote>  $notesByAlias
     * @return array{references: array<string, string>, referencedBy: array<string, string>}|null alias => one-line description; null when the node is unknown
     */
    public function __invoke(array $notesByAlias, string $alias, TraceDirection $traceDirection): ?array
    {
        $parsedNote = $notesByAlias[$alias] ?? null;

        if ($parsedNote === null) {
            return null;
        }

        return [
            'references' => $traceDirection === TraceDirection::In
                ? []
                : $this->describe(aliases: $parsedNote->links, notesByAlias: $notesByAlias),
            'referencedBy' => $traceDirection === TraceDirection::Out
                ? []
                : $this->describe(aliases: $parsedNote->referencedBy, notesByAlias: $notesByAlias),
        ];
    }

    /**
     * @param  list<string>  $aliases
     * @param  array<string, ParsedNote>  $notesByAlias
     * @return array<string, string>
     */
    private function describe(array $aliases, array $notesByAlias): array
    {
        $described = [];

        foreach ($aliases as $alias) {
            $description = isset($notesByAlias[$alias])
                ? (string) preg_replace('/\s+/', ' ', $notesByAlias[$alias]->description)
                : '';

            $described[$alias] = $description;
        }

        return $described;
    }
}
