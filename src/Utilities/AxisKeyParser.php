<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

#[\EtruscanNode('axis-key-parser')]
#[\EtruscanLayer('utility')]
#[\EtruscanContext('cli')]
final class AxisKeyParser
{
    /**
     * @return list<string>
     */
    public static function parse(string $rawAxisKeys): array
    {
        $axisKeys = array_map(trim(...), explode(',', $rawAxisKeys));
        $axisKeys = array_filter($axisKeys, static fn (string $axisKey): bool => $axisKey !== '');

        return array_values(array_unique($axisKeys));
    }
}
