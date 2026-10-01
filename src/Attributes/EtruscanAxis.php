<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Attributes;

#[\EtruscanNode('etruscan-axis')]
#[\EtruscanLayer('attribute')]
#[\EtruscanContext('taxonomy')]
abstract readonly class EtruscanAxis
{
    public function __construct(public string $value) {}
}
