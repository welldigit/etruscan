<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Enums;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('usage-event-type')]
#[EtruscanLayer('enum')]
#[EtruscanContext('usage')]
enum UsageEventType: string
{
    case Lookup = 'lookup';
    case Search = 'search';
    case Trace = 'trace';
    case Overview = 'overview';
}
