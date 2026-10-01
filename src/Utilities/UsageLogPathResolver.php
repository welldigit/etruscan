<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

#[\EtruscanNode('usage-log-path-resolver')]
#[\EtruscanLayer('utility')]
#[\EtruscanContext('usage')]
final class UsageLogPathResolver
{
    public static function resolve(string $vaultPath): string
    {
        return ReportsPathResolver::resolve($vaultPath, 'usage.jsonl');
    }
}
