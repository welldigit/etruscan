<?php

declare(strict_types=1);

use WellDigit\Etruscan\Attributes\EtruscanAxis;

/**
 * Global-namespace twin of {@see WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer},
 * written without an import: #[\EtruscanLayer('action')].
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class EtruscanLayer extends EtruscanAxis {}
