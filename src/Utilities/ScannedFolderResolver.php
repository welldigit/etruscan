<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

#[\EtruscanNode('scanned-folder-resolver')]
#[\EtruscanLayer('utility')]
#[\EtruscanContext('scan')]
final class ScannedFolderResolver
{
    /**
     * @return list<string>
     */
    public static function resolve(mixed $scannedFolders): array
    {
        $paths = match (true) {
            is_array($scannedFolders) => array_filter($scannedFolders, is_string(...)),
            is_string($scannedFolders) => array_map(trim(...), explode(',', $scannedFolders)),
            default => [],
        };

        $paths = array_unique(array_filter($paths, static fn (string $path): bool => $path !== ''));

        return array_values(array_map(AbsolutePathResolver::resolve(...), $paths));
    }
}
