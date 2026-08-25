<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Enums;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('check-category')]
#[EtruscanLayer('enum')]
#[EtruscanContext('check')]
enum CheckCategory: string
{
    case DuplicateAlias = 'duplicate-alias';
    case UnknownVocabulary = 'unknown-vocabulary';
    case BrokenLink = 'broken-link';
}
