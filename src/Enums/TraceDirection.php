<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Enums;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('trace-direction')]
#[EtruscanLayer('enum')]
#[EtruscanContext('mcp')]
enum TraceDirection: string
{
    case Out = 'out';
    case In = 'in';
    case Both = 'both';
}
