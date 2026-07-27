<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('usage-log-path-resolver')]
#[EtruscanLayer('utility')]
#[EtruscanContext('usage')]
final class UsageLogPathResolver
{
    public static function resolve(string $vaultPath): string
    {
        return $vaultPath.DIRECTORY_SEPARATOR.'usage.jsonl';
    }
}
