<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Attributes\EtruscanAxis;
use WellDigit\Etruscan\Attributes\EtruscanNode;

#[\EtruscanNode('axis-attribute-reader')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('scan')]
#[\EtruscanContext('taxonomy')]
final class AxisAttributeReader
{
    /** The namespaced node attribute and its global twin, the one written without an import. */
    private const array NODE_ATTRIBUTES = [EtruscanNode::class, \EtruscanNode::class];

    /** @var array<string, bool> */
    private array $axisCache = [];

    public function isNodeAttribute(string $fqcn): bool
    {
        $fqcn = ltrim($fqcn, '\\');

        return array_any(
            self::NODE_ATTRIBUTES,
            static fn (string $nodeAttribute): bool => strcasecmp($fqcn, $nodeAttribute) === 0,
        );
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

    /**
     * An Etruscan-named attribute that resolves to no class at all: the leading backslash
     * or the import is missing, so PHP resolved the name inside the annotated class's own
     * namespace and the node silently left the map.
     */
    public function isUnresolvedEtruscanAttribute(string $fqcn): bool
    {
        $fqcn = ltrim($fqcn, '\\');

        return preg_match('/^Etruscan(?=[A-Z])/', class_basename($fqcn)) === 1
            && ! $this->isNodeAttribute($fqcn)
            && ! class_exists($fqcn);
    }
}
