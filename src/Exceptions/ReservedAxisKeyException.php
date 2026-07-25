<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Exceptions;

use RuntimeException;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('reserved-axis-key')]
#[EtruscanLayer('exception')]
#[EtruscanContext('taxonomy')]
final class ReservedAxisKeyException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function make(string $alias, string $axisKey): self
    {
        return new self(sprintf(
            'Etruscan axis key [%s] on node [%s] collides with a reserved identity frontmatter key. Rename the axis class.',
            $axisKey,
            $alias,
        ));
    }
}
