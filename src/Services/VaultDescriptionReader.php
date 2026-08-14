<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

/**
 * Reads the human-written descriptions back out of the vault, keyed by alias.
 * The notes are their only home — nothing derives a description from code — so
 * anything that wants to show one (the graph page, for instance) comes here.
 */
#[EtruscanNode('vault-description-reader')]
#[EtruscanLayer('service')]
#[EtruscanContext('vault')]
#[EtruscanContext('projection')]
final readonly class VaultDescriptionReader
{
    public function __construct(
        private VaultReader $vaultReader,
    ) {}

    /**
     * @return array<string, string> alias => description, empty ones omitted
     */
    public function __invoke(string $vaultPath, string $markerKey): array
    {
        $descriptionsByAlias = [];

        foreach (($this->vaultReader)($vaultPath, $markerKey) as $alias => $parsedNote) {
            if ($parsedNote->description !== '') {
                $descriptionsByAlias[$alias] = $parsedNote->description;
            }
        }

        return $descriptionsByAlias;
    }
}
