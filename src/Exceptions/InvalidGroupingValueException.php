<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Exceptions;

use RuntimeException;

#[\EtruscanNode('invalid-grouping-value')]
#[\EtruscanLayer('exception')]
#[\EtruscanContext('vault')]
final class InvalidGroupingValueException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function make(string $alias, string $axisKey, string $rawValue): self
    {
        return new self(sprintf(
            'Etruscan grouping value [%s] on axis [%s] for node [%s] cannot be sanitized into a directory name.',
            $rawValue,
            $axisKey,
            $alias,
        ));
    }
}
