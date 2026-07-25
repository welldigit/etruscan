<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Payloads;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Enums\CheckSeverity;

#[EtruscanNode('check-finding')]
#[EtruscanLayer('payload')]
#[EtruscanContext('check')]
final readonly class CheckFinding
{
    public function __construct(
        public CheckCategory $category,
        public CheckSeverity $severity,
        public string $message,
    ) {}
}
