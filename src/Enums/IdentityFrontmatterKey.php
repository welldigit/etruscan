<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Enums;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('identity-frontmatter-key')]
#[EtruscanLayer('enum')]
#[EtruscanContext('projection')]
enum IdentityFrontmatterKey: string
{
    case Alias = 'alias';
    case ClassShortName = 'class';
    case Extends = 'extends';
    case Fqcn = 'fqcn';
    case Source = 'source';

    public static function isReserved(string $axisKey): bool
    {
        return self::tryFrom($axisKey) !== null;
    }
}
