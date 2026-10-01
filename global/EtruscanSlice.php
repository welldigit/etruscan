<?php

declare(strict_types=1);

use WellDigit\Etruscan\Attributes\EtruscanAxis;

/**
 * Global-namespace twin of {@see WellDigit\Etruscan\Attributes\Vocabulary\EtruscanSlice},
 * written without an import: #[\EtruscanSlice('checkout')].
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class EtruscanSlice extends EtruscanAxis {}
