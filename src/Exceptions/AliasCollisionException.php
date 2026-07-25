<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Exceptions;

use RuntimeException;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('alias-collision')]
#[EtruscanLayer('exception')]
#[EtruscanContext('projection')]
final class AliasCollisionException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function make(string $alias, string $firstFqcn, string $secondFqcn): self
    {
        return new self(sprintf(
            'Etruscan alias collision: [%s] is claimed by both [%s] and [%s]. Node aliases must be unique.',
            $alias,
            $firstFqcn,
            $secondFqcn,
        ));
    }
}
