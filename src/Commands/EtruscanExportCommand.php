<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Mcp\Services\MapReader;
use WellDigit\Etruscan\Services\MapDigestRenderer;
use WellDigit\Etruscan\Services\VaultReader;
use WellDigit\Etruscan\Utilities\AbsolutePathResolver;
use WellDigit\Etruscan\Utilities\DigestPathResolver;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

/**
 * Emits the map as one markdown index, for a CLAUDE.md import.
 *
 * Note this writes the one artifact a consumer is meant to commit, so unlike
 * the graph and usage pages it does not land in `.reports/` — that folder
 * seeds its own .gitignore, which is exactly backwards for a file that should
 * travel with the repo.
 */
#[Description('Export the map as a markdown index for an agent\'s CLAUDE.md')]
#[Signature('etruscan:export
        {--output= : Override the output path (default: map.md inside the vault)}
        {--stdout : Print the index instead of writing it}')]
#[\EtruscanNode('etruscan-export')]
#[\EtruscanLayer('command')]
#[\EtruscanContext('export')]
#[\EtruscanContext('cli')]
final class EtruscanExportCommand extends Command
{
    public function handle(
        VaultReader $vaultReader,
        MapDigestRenderer $mapDigestRenderer,
    ): int {
        $vaultPath = EtruscanConfig::vaultPath();
        $markerKey = EtruscanConfig::markerKey();

        $notesByAlias = $vaultReader($vaultPath, $markerKey);

        if ($notesByAlias === []) {
            $this->error(MapReader::EMPTY_MAP_MESSAGE);

            return self::FAILURE;
        }

        $digest = $mapDigestRenderer(
            $notesByAlias,
            EtruscanConfig::exportAxis(),
            $markerKey,
        );

        if ($this->option('stdout')) {
            $this->line($digest);

            return self::SUCCESS;
        }

        $outputOption = $this->option('output');
        $outputPath = is_string($outputOption) && $outputOption !== ''
            ? AbsolutePathResolver::resolve($outputOption)
            : DigestPathResolver::resolve($vaultPath);

        File::ensureDirectoryExists(dirname($outputPath));
        File::replace($outputPath, $digest, 0666 & ~umask());

        $this->info(sprintf('Exported %d node(s) into %s', count($notesByAlias), $outputPath));
        $this->line(sprintf('  Add this line to CLAUDE.md so it loads at session start:  @%s', $this->importPath($outputPath)));

        return self::SUCCESS;
    }

    /**
     * CLAUDE.md resolves an @import relative to itself, so the line is only
     * usable if the path is relative to the project root.
     */
    private function importPath(string $outputPath): string
    {
        $base = base_path().DIRECTORY_SEPARATOR;

        return str_starts_with($outputPath, $base)
            ? substr($outputPath, strlen($base))
            : $outputPath;
    }
}
