<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Services;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Services\VaultReader;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

/**
 * The opening move every map tool makes: read the configured vault. Shared so
 * the four tools cannot drift apart on which vault they read, and so the
 * sentence an agent gets when there is no map yet — its only instruction about
 * what to do next — is written once.
 */
#[EtruscanNode('map-reader')]
#[EtruscanLayer('service')]
#[EtruscanContext('mcp')]
final readonly class MapReader
{
    public const string EMPTY_MAP_MESSAGE = 'The map is empty — generate it first with: php artisan etruscan:generate';

    public function __construct(
        private VaultReader $vaultReader,
    ) {}

    /**
     * @return array<string, ParsedNote> alias => note, empty when there is no map
     */
    public function __invoke(): array
    {
        return ($this->vaultReader)(EtruscanConfig::vaultPath(), EtruscanConfig::markerKey());
    }
}
