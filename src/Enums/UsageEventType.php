<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Enums;

#[\EtruscanNode('usage-event-type')]
#[\EtruscanLayer('enum')]
#[\EtruscanContext('usage')]
enum UsageEventType: string
{
    case Lookup = 'lookup';
    case Search = 'search';
    case Trace = 'trace';
    case Overview = 'overview';
}
