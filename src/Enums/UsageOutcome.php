<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Enums;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('usage-outcome')]
#[EtruscanLayer('enum')]
#[EtruscanContext('usage')]
enum UsageOutcome: string
{
    case Hit = 'hit';
    case Miss = 'miss';
}
