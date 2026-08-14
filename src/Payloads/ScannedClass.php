<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Payloads;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('scanned-class')]
#[EtruscanLayer('payload')]
#[EtruscanContext('scan')]
final readonly class ScannedClass
{
    /**
     * @param  string  $fqcn  Fully-qualified class name (no leading backslash).
     * @param  array<string, list<string>>  $axes  Axis values keyed by axis name.
     * @param  list<string>  $references  Resolved FQCNs the class refers to: imports, type hints, instantiations, attributes.
     * @param  string  $sourcePath  Absolute path to the source file.
     * @param  string|null  $alias  Node alias, or null when the class carries no #[EtruscanNode].
     * @param  string|null  $extendsFqcn  Parent class FQCN, if any.
     */
    public function __construct(
        public string $fqcn,
        public array $axes,
        public array $references,
        public string $sourcePath,
        public ?string $alias = null,
        public ?string $extendsFqcn = null,
    ) {}

    public function isNode(): bool
    {
        return $this->alias !== null;
    }
}
