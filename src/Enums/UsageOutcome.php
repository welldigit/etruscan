<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Enums;

#[\EtruscanNode('usage-outcome')]
#[\EtruscanLayer('enum')]
#[\EtruscanContext('usage')]
enum UsageOutcome: string
{
    case Hit = 'hit';
    case Miss = 'miss';
}
