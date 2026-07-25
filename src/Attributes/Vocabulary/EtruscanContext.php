<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Attributes\Vocabulary;

use Attribute;
use WellDigit\Etruscan\Attributes\EtruscanAxis;
use WellDigit\Etruscan\Attributes\EtruscanNode;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
#[EtruscanNode('etruscan-context')]
#[EtruscanLayer('attribute')]
#[EtruscanContext('taxonomy')]
final readonly class EtruscanContext extends EtruscanAxis {}
