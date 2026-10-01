<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Services;

use WellDigit\Etruscan\Enums\IdentityFrontmatterKey;
use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

#[\EtruscanNode('map-search')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('mcp')]
final readonly class MapSearch
{
    public const int RESULT_LIMIT = 10;

    /**
     * Ranks notes against the query: alias match first, then class/fqcn,
     * then axis values, then description text.
     *
     * The query is split into words — hyphens, underscores, backslashes and
     * dots count as spaces, so "monitor create", "monitor-create" and
     * "Monitor\\Create" all ask the same thing — and every word must match
     * somewhere on a note for it to rank at all.
     *
     * @param  array<string, ParsedNote>  $notesByAlias
     * @return list<ParsedNote>
     */
    public function __invoke(array $notesByAlias, string $query): array
    {
        $phrase = $this->normalize($query);

        if ($phrase === '') {
            return [];
        }

        $tokens = explode(' ', $phrase);
        $markerKey = EtruscanConfig::markerKey();

        $scores = [];

        foreach ($notesByAlias as $alias => $parsedNote) {
            $score = $this->score(parsedNote: $parsedNote, tokens: $tokens, phrase: $phrase, alias: (string) $alias, markerKey: $markerKey);

            if ($score > 0) {
                $scores[$alias] = $score;
            }
        }

        arsort($scores);

        return array_map(
            static fn (string $alias): ParsedNote => $notesByAlias[$alias],
            array_slice(array_map(strval(...), array_keys($scores)), 0, self::RESULT_LIMIT),
        );
    }

    /**
     * @param  list<string>  $tokens
     */
    private function score(ParsedNote $parsedNote, array $tokens, string $phrase, string $alias, string $markerKey): int
    {
        $normalizedAlias = $this->normalize($alias);

        $identities = [];

        foreach ([IdentityFrontmatterKey::ClassShortName->value, IdentityFrontmatterKey::Fqcn->value] as $key) {
            $value = $parsedNote->frontmatter[$key] ?? null;

            if (is_string($value)) {
                $identities[] = $this->normalize($value);
            }
        }

        $axisValues = [];

        foreach ($parsedNote->frontmatter as $key => $value) {
            if (IdentityFrontmatterKey::isReserved($key) || $key === $markerKey) {
                continue;
            }

            foreach (is_array($value) ? $value : [$value] as $axisValue) {
                $axisValues[] = $this->normalize($axisValue);
            }
        }

        $description = $this->normalize($parsedNote->description);
        $manual = $this->normalize($parsedNote->manual);

        $score = $normalizedAlias === $phrase ? 100 : 0;

        foreach ($tokens as $token) {
            $tokenScore = str_contains($normalizedAlias, $token) ? 50 : 0;

            foreach ($identities as $identity) {
                $tokenScore += str_contains($identity, $token) ? 30 : 0;
            }

            foreach ($axisValues as $axisValue) {
                $tokenScore += str_contains($axisValue, $token) ? 20 : 0;
            }

            $tokenScore += str_contains($description, $token) ? 10 : 0;
            $tokenScore += str_contains($manual, $token) ? 5 : 0;

            if ($tokenScore === 0) {
                return 0;
            }

            $score += $tokenScore;
        }

        return $score;
    }

    private function normalize(string $text): string
    {
        return trim((string) preg_replace('/[\s\-_\\\\\/.:]+/', ' ', strtolower($text)));
    }
}
