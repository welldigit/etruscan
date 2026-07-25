<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Enums;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('check-severity')]
#[EtruscanLayer('enum')]
#[EtruscanContext('check')]
enum CheckSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
}
