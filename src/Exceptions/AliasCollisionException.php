<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Exceptions;

use RuntimeException;

#[\EtruscanNode('alias-collision')]
#[\EtruscanLayer('exception')]
#[\EtruscanContext('projection')]
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
