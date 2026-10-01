<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Enums;

#[\EtruscanNode('check-category')]
#[\EtruscanLayer('enum')]
#[\EtruscanContext('check')]
enum CheckCategory: string
{
    case DuplicateAlias = 'duplicate-alias';
    case UnknownVocabulary = 'unknown-vocabulary';
    case UnresolvedAttribute = 'unresolved-attribute';
    case BrokenLink = 'broken-link';
    case StaleMap = 'stale-map';
    case ScanIncomplete = 'scan-incomplete';
    case StaleDigest = 'stale-digest';
}
