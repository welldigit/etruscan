<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Enums\CheckSeverity;
use WellDigit\Etruscan\Payloads\CheckFinding;
use WellDigit\Etruscan\Utilities\DigestPathResolver;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

/** Checks exported content rather than timestamps, including after a fresh checkout. */
#[\EtruscanNode('digest-staleness-checker')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('check')]
#[\EtruscanContext('export')]
final readonly class DigestStalenessChecker
{
    public function __construct(private VaultReader $vaultReader, private MapDigestRenderer $mapDigestRenderer) {}

    /**
     * @return list<CheckFinding>
     */
    public function __invoke(string $vaultPath): array
    {
        $digestPath = DigestPathResolver::resolve($vaultPath);

        if (! File::isFile($digestPath) || ! File::isDirectory($vaultPath)) {
            return [];
        }

        $notes = ($this->vaultReader)($vaultPath, EtruscanConfig::markerKey());

        if (File::get($digestPath) === ($this->mapDigestRenderer)($notes, EtruscanConfig::exportAxis(), EtruscanConfig::markerKey())) {
            return [];
        }

        return [new CheckFinding(
            category: CheckCategory::StaleDigest,
            severity: CheckSeverity::Warning,
            message: sprintf(
                $notes === []
                    ? 'The exported index [%s] remains but there are no generated nodes — remove the obsolete export or restore and regenerate the map.'
                    : 'The exported index [%s] differs from the notes it indexes — refresh it with: php artisan etruscan:export',
                DigestPathResolver::FILE_NAME,
            ),
        )];
    }
}
