<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Enums;

#[\EtruscanNode('check-severity')]
#[\EtruscanLayer('enum')]
#[\EtruscanContext('check')]
enum CheckSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
}
