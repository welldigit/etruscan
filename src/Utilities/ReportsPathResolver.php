<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('reports-path-resolver')]
#[EtruscanLayer('utility')]
#[EtruscanContext('usage')]
final class ReportsPathResolver
{
    private const string REPORTS_FOLDER = '.reports';

    public static function resolve(string $vaultPath, string $fileName): string
    {
        return $vaultPath.DIRECTORY_SEPARATOR.self::REPORTS_FOLDER.DIRECTORY_SEPARATOR.$fileName;
    }
}
