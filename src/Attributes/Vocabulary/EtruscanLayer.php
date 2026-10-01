<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Attributes\Vocabulary;

use Attribute;
use WellDigit\Etruscan\Attributes\EtruscanAxis;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
#[\EtruscanNode('etruscan-layer')]
#[\EtruscanLayer('attribute')]
#[\EtruscanContext('taxonomy')]
final readonly class EtruscanLayer extends EtruscanAxis {}
