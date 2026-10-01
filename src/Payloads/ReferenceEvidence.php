<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Payloads;

#[\EtruscanNode('reference-evidence')]
#[\EtruscanLayer('payload')]
#[\EtruscanContext('scan')]
final readonly class ReferenceEvidence
{
    public function __construct(
        public string $target,
        public string $kind,
        public int $line,
    ) {}
}
