<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Payloads;

#[\EtruscanNode('scanned-class')]
#[\EtruscanLayer('payload')]
#[\EtruscanContext('scan')]
final readonly class ScannedClass
{
    /**
     * @param  string  $fqcn  Fully-qualified class name (no leading backslash).
     * @param  array<string, list<string>>  $axes  Axis values keyed by axis name.
     * @param  list<string>  $references  Resolved class usages; unused imports are excluded.
     * @param  string  $sourcePath  Absolute path to the source file.
     * @param  string|null  $alias  Node alias, or null when the class carries no #[\EtruscanNode].
     * @param  string|null  $extendsFqcn  Parent class FQCN, if any.
     * @param  list<ReferenceEvidence>  $evidence  Class-scoped usage locations in sourcePath.
     */
    public function __construct(
        public string $fqcn,
        public array $axes,
        public array $references,
        public string $sourcePath,
        public ?string $alias = null,
        public ?string $extendsFqcn = null,
        public array $evidence = [],
    ) {}

    public function isNode(): bool
    {
        return $this->alias !== null;
    }
}
