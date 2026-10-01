<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Services;

use WellDigit\Etruscan\Payloads\ParsedNote;

#[\EtruscanNode('node-lookup')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('mcp')]
final readonly class NodeLookup
{
    private const int SUGGESTION_DISTANCE = 4;

    private const int SUGGESTION_LIMIT = 5;

    /**
     * @param  array<string, ParsedNote>  $notesByAlias
     * @return array{note: ParsedNote|null, suggestions: list<string>}
     */
    public function __invoke(array $notesByAlias, string $alias): array
    {
        if (isset($notesByAlias[$alias])) {
            return ['note' => $notesByAlias[$alias], 'suggestions' => []];
        }

        return ['note' => null, 'suggestions' => $this->closest(aliases: array_keys($notesByAlias), wanted: $alias)];
    }

    /**
     * @param  list<string>  $aliases
     * @return list<string>
     */
    private function closest(array $aliases, string $wanted): array
    {
        $distances = [];

        foreach ($aliases as $candidate) {
            $distance = levenshtein(strtolower($wanted), strtolower($candidate));

            if ($distance <= self::SUGGESTION_DISTANCE || str_contains($candidate, strtolower($wanted))) {
                $distances[$candidate] = $distance;
            }
        }

        asort($distances);

        return array_slice(array_keys($distances), 0, self::SUGGESTION_LIMIT);
    }
}
