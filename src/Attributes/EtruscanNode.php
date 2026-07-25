<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Attributes;

use Attribute;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[Attribute(Attribute::TARGET_CLASS)]
#[EtruscanNode('etruscan-node')]
#[EtruscanLayer('attribute')]
#[EtruscanContext('taxonomy')]
final readonly class EtruscanNode
{
    public function __construct(public string $alias) {}
}
