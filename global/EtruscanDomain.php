<?php

declare(strict_types=1);

use WellDigit\Etruscan\Attributes\EtruscanAxis;

/**
 * Global-namespace twin of {@see WellDigit\Etruscan\Attributes\Vocabulary\EtruscanDomain},
 * written without an import: #[\EtruscanDomain('booking')].
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class EtruscanDomain extends EtruscanAxis {}
