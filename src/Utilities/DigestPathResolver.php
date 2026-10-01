<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

/**
 * Where the exported index lives: `{vault}/map.md`.
 *
 * Pointedly not inside `.reports/`. That folder exists to keep machine-local
 * artifacts out of git and seeds its own .gitignore to enforce it; the index is
 * the one thing this package produces that a consumer should commit, because it
 * is read by every agent that opens the repo. One resolver so the exporter and
 * the staleness check cannot disagree about where it is.
 */
#[\EtruscanNode('digest-path-resolver')]
#[\EtruscanLayer('utility')]
#[\EtruscanContext('export')]
final class DigestPathResolver
{
    public const string FILE_NAME = 'map.md';

    public static function resolve(string $vaultPath): string
    {
        return $vaultPath.DIRECTORY_SEPARATOR.self::FILE_NAME;
    }
}
