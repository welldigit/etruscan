<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Services;

use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Services\ReferenceIndex;
use WellDigit\Etruscan\Services\SourceFreshness;
use WellDigit\Etruscan\Services\VaultReader;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

/**
 * The opening move every map tool makes: read the configured vault. Shared so
 * the four tools cannot drift apart on which vault they read, and so the
 * sentence an agent gets when there is no map yet — its only instruction about
 * what to do next — is written once.
 */
#[\EtruscanNode('map-reader')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('mcp')]
final class MapReader
{
    public const string EMPTY_MAP_MESSAGE = 'The map is empty — generate it first with: php artisan etruscan:generate';

    private ?string $freshness = null;

    /**
     * The notes the last read returned, handed to the freshness check so its
     * structure hash reuses this read instead of parsing the vault again.
     *
     * @var array<string, ParsedNote>|null
     */
    private ?array $notesByAlias = null;

    public function __construct(
        private readonly VaultReader $vaultReader,
        private readonly SourceFreshness $sourceFreshness,
        private readonly ReferenceIndex $referenceIndex,
    ) {}

    public function freshnessNotice(): string
    {
        $status = $this->freshnessStatus();

        // A stale index describes a vault that no longer exists, so its orphan
        // count would be wrong; it is reported only while the index matches.
        $index = in_array($status, ['current', 'incomplete'], true)
            ? $this->referenceIndex->read(EtruscanConfig::vaultPath())
            : null;

        return SourceFreshness::describe($status, $index === null ? 0 : count($index['orphans']));
    }

    public function freshnessStatus(): string
    {
        return $this->freshness ??= ($this->sourceFreshness)(EtruscanConfig::vaultPath(), $this->notesByAlias);
    }

    /**
     * @return array<string, ParsedNote> alias => note, empty when there is no map
     */
    public function __invoke(): array
    {
        $this->freshness = null;

        return $this->notesByAlias = ($this->vaultReader)(EtruscanConfig::vaultPath(), EtruscanConfig::markerKey());
    }
}
