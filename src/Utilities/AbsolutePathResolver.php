<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

#[\EtruscanNode('absolute-path-resolver')]
#[\EtruscanLayer('utility')]
#[\EtruscanContext('vault')]
#[\EtruscanContext('scan')]
final class AbsolutePathResolver
{
    public static function resolve(string $path): string
    {
        return self::isAbsolute($path) ? $path : base_path($path);
    }

    private static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1;
    }
}
