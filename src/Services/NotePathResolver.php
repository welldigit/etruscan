<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Exceptions\InvalidGroupingValueException;
use WellDigit\Etruscan\Payloads\NoteContent;

#[\EtruscanNode('note-path-resolver')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('vault')]
final readonly class NotePathResolver
{
    /**
     * @param  list<string>  $groupByAxisKeys  Nesting order; empty keeps the vault flat.
     */
    public function __construct(
        private array $groupByAxisKeys,
    ) {}

    public function __invoke(NoteContent $noteContent): string
    {
        $segments = [];

        foreach ($this->groupByAxisKeys as $groupByAxisKey) {
            $axisValue = $noteContent->frontmatter[$groupByAxisKey] ?? null;

            if ($axisValue === null) {
                continue;
            }

            $values = is_array($axisValue) ? $axisValue : [$axisValue];

            if ($values === []) {
                continue;
            }

            sort($values);

            $segments[] = $this->sanitizeDirectory(
                alias: $noteContent->alias,
                axisKey: $groupByAxisKey,
                rawValue: $values[0],
            );
        }

        $segments[] = $noteContent->alias.'.md';

        return implode('/', $segments);
    }

    private function sanitizeDirectory(string $rawValue, string $axisKey, string $alias): string
    {
        $directory = strtolower($rawValue);
        $directory = (string) preg_replace('/[^a-z0-9_-]+/', '-', $directory);
        $directory = trim((string) preg_replace('/-+/', '-', $directory), '-');

        if ($directory === '') {
            throw InvalidGroupingValueException::make(alias: $alias, axisKey: $axisKey, rawValue: $rawValue);
        }

        return $directory;
    }
}
