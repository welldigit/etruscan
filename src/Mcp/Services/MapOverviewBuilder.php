<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Services;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\IdentityFrontmatterKey;
use WellDigit\Etruscan\Payloads\ParsedNote;

#[EtruscanNode('map-overview-builder')]
#[EtruscanLayer('service')]
#[EtruscanContext('mcp')]
final readonly class MapOverviewBuilder
{
    /**
     * Groups node aliases by the values of one axis; nodes without the axis
     * land under "(none)". Falls back to the first axis present on any note
     * when the requested axis appears nowhere.
     *
     * @param  array<string, ParsedNote>  $notesByAlias
     * @return array{axis: string, groups: array<string, list<string>>}
     */
    public function __invoke(array $notesByAlias, string $axis, string $markerKey): array
    {
        if (! $this->axisIsPresent(notesByAlias: $notesByAlias, axis: $axis, markerKey: $markerKey)) {
            $axis = $this->firstAxisPresent(notesByAlias: $notesByAlias, markerKey: $markerKey) ?? $axis;
        }

        $groups = [];

        foreach ($notesByAlias as $alias => $parsedNote) {
            $value = $parsedNote->frontmatter[$axis] ?? null;

            $values = match (true) {
                is_array($value) => $value,
                is_string($value) => [$value],
                default => ['(none)'],
            };

            foreach ($values as $axisValue) {
                $groups[$axisValue][] = $alias;
            }
        }

        ksort($groups);

        return ['axis' => $axis, 'groups' => $groups];
    }

    /**
     * @param  array<string, ParsedNote>  $notesByAlias
     */
    private function axisIsPresent(array $notesByAlias, string $axis, string $markerKey): bool
    {
        if (IdentityFrontmatterKey::isReserved($axis) || $axis === $markerKey) {
            return false;
        }

        return array_any(
            $notesByAlias,
            static fn (ParsedNote $parsedNote): bool => array_key_exists($axis, $parsedNote->frontmatter),
        );
    }

    /**
     * @param  array<string, ParsedNote>  $notesByAlias
     */
    private function firstAxisPresent(array $notesByAlias, string $markerKey): ?string
    {
        foreach ($notesByAlias as $parsedNote) {
            foreach (array_keys($parsedNote->frontmatter) as $key) {
                if (! IdentityFrontmatterKey::isReserved($key) && $key !== $markerKey) {
                    return $key;
                }
            }
        }

        return null;
    }
}
