<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Enums\IdentityFrontmatterKey;
use WellDigit\Etruscan\Payloads\ContextFootprint;
use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Utilities\AbsolutePathResolver;

/**
 * Measures the trade the map exists to make: the characters it costs an agent
 * against the characters of source it indexes.
 *
 * The usage log already records what the map delivered. It cannot record how
 * much code that pointed into, so that half was never measured — and it is the
 * half the package's claim rests on. Computed from the filesystem, with no
 * model call and nothing to estimate.
 */
#[\EtruscanNode('context-footprint-calculator')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('usage')]
final readonly class ContextFootprintCalculator
{
    /**
     * @param  array<string, ParsedNote>  $notesByAlias
     */
    public function __invoke(array $notesByAlias): ContextFootprint
    {
        $noteChars = 0;
        $sourceChars = 0;
        $sourcesMissing = 0;

        foreach ($notesByAlias as $parsedNote) {
            $noteChars += $this->charsOf($parsedNote->path);

            $source = $parsedNote->frontmatter[IdentityFrontmatterKey::Source->value] ?? null;

            if (! is_string($source) || $source === '') {
                $sourcesMissing++;

                continue;
            }

            $chars = $this->charsOf(AbsolutePathResolver::resolve($source));

            if ($chars === 0) {
                $sourcesMissing++;

                continue;
            }

            $sourceChars += $chars;
        }

        return new ContextFootprint(
            notes: count($notesByAlias),
            noteChars: $noteChars,
            sourceChars: $sourceChars,
            sourcesMissing: $sourcesMissing,
        );
    }

    /**
     * Unreadable counts as nothing rather than throwing: a footprint is a
     * diagnostic, and a stale path should not take the usage report down.
     */
    private function charsOf(?string $path): int
    {
        if ($path === null || ! File::isFile($path)) {
            return 0;
        }

        return mb_strlen(File::get($path));
    }
}
