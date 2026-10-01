<?php

declare(strict_types=1);

/**
 * Global-namespace twin of {@see WellDigit\Etruscan\Attributes\EtruscanNode}, so a class
 * joins the map without an import: #[\EtruscanNode('alias')]. The leading backslash is
 * what points at this class; without it PHP resolves the name inside your own namespace.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class EtruscanNode
{
    public function __construct(public string $alias) {}
}
