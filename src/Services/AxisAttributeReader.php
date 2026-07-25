<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Attributes\EtruscanAxis;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('axis-attribute-reader')]
#[EtruscanLayer('service')]
#[EtruscanContext('scan')]
#[EtruscanContext('taxonomy')]
final class AxisAttributeReader
{
    /** @var array<string, bool> */
    private array $axisCache = [];

    public function isNodeAttribute(string $fqcn): bool
    {
        return ltrim($fqcn, '\\') === EtruscanNode::class;
    }

    public function isAxisAttribute(string $fqcn): bool
    {
        $fqcn = ltrim($fqcn, '\\');

        return $this->axisCache[$fqcn] ??= class_exists($fqcn) && is_subclass_of($fqcn, EtruscanAxis::class);
    }

    public function resolveAxisKey(string $fqcn): string
    {
        $shortName = class_basename($fqcn);
        $shortName = preg_replace('/^Etruscan(?=[A-Z])/', '', $shortName) ?? $shortName;

        return strtolower($shortName);
    }
}
