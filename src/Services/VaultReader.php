<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Payloads\ParsedNote;

#[EtruscanNode('vault-reader')]
#[EtruscanLayer('service')]
#[EtruscanContext('vault')]
#[EtruscanContext('mcp')]
final readonly class VaultReader
{
    public function __construct(
        private NoteParser $noteParser,
    ) {}

    /**
     * @return array<string, ParsedNote> alias => parsed note (generated notes only)
     */
    public function __invoke(string $vaultPath, string $markerKey): array
    {
        if (! File::isDirectory($vaultPath)) {
            return [];
        }

        $notesByAlias = [];

        foreach (File::allFiles($vaultPath) as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $parsedNote = ($this->noteParser)(File::get($file->getPathname()));

            if ($parsedNote->alias === null || ! array_key_exists($markerKey, $parsedNote->frontmatter)) {
                continue;
            }

            $notesByAlias[$parsedNote->alias] = $parsedNote;
        }

        ksort($notesByAlias);

        return $notesByAlias;
    }
}
