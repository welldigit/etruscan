<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Enums;

#[\EtruscanNode('trace-direction')]
#[\EtruscanLayer('enum')]
#[\EtruscanContext('mcp')]
enum TraceDirection: string
{
    case Out = 'out';
    case In = 'in';
    case Both = 'both';
}
