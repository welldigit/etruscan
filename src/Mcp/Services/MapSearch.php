<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Services;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\IdentityFrontmatterKey;
use WellDigit\Etruscan\Payloads\ParsedNote;

#[EtruscanNode('map-search')]
#[EtruscanLayer('service')]
#[EtruscanContext('mcp')]
final readonly class MapSearch
{
    private const int RESULT_LIMIT = 10;

    /**
     * Ranks notes against the query: alias match first, then class/fqcn,
     * then axis values, then description text.
     *
     * @param  array<string, ParsedNote>  $notesByAlias
     * @return list<ParsedNote>
     */
    public function __invoke(array $notesByAlias, string $query): array
    {
        $needle = strtolower(trim($query));

        if ($needle === '') {
            return [];
        }

        $scores = [];

        foreach ($notesByAlias as $alias => $parsedNote) {
            $score = $this->score(parsedNote: $parsedNote, needle: $needle, alias: $alias);

            if ($score > 0) {
                $scores[$alias] = $score;
            }
        }

        arsort($scores);

        return array_map(
            static fn (string $alias): ParsedNote => $notesByAlias[$alias],
            array_slice(array_keys($scores), 0, self::RESULT_LIMIT),
        );
    }

    private function score(ParsedNote $parsedNote, string $needle, string $alias): int
    {
        $score = 0;

        if (strtolower($alias) === $needle) {
            $score += 100;
        } elseif (str_contains(strtolower($alias), $needle)) {
            $score += 50;
        }

        foreach ([IdentityFrontmatterKey::ClassShortName->value, IdentityFrontmatterKey::Fqcn->value] as $key) {
            $value = $parsedNote->frontmatter[$key] ?? null;

            if (is_string($value) && str_contains(strtolower($value), $needle)) {
                $score += 30;
            }
        }

        foreach ($parsedNote->frontmatter as $key => $value) {
            if (IdentityFrontmatterKey::isReserved($key)) {
                continue;
            }

            $values = is_array($value) ? $value : [$value];

            foreach ($values as $axisValue) {
                if (str_contains(strtolower($axisValue), $needle)) {
                    $score += 20;
                }
            }
        }

        if (str_contains(strtolower($parsedNote->description), $needle)) {
            $score += 10;
        }

        if (str_contains(strtolower($parsedNote->manual), $needle)) {
            $score += 5;
        }

        return $score;
    }
}
