<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Attributes;

use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('etruscan-axis')]
#[EtruscanLayer('attribute')]
#[EtruscanContext('taxonomy')]
abstract readonly class EtruscanAxis
{
    public function __construct(public string $value) {}
}
