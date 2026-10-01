<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use WellDigit\Etruscan\Payloads\ParsedNote;

#[\EtruscanNode('vault-reader')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('vault')]
#[\EtruscanContext('mcp')]
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

            $parsedNote = ($this->noteParser)(
                $this->utf8($file->getPathname()),
                $this->relativePath($file->getPathname()),
            );

            if ($parsedNote->alias === null || ! array_key_exists($markerKey, $parsedNote->frontmatter)) {
                continue;
            }

            $notesByAlias[$parsedNote->alias] = $parsedNote;
        }

        ksort($notesByAlias);

        return $notesByAlias;
    }

    /**
     * Notes are served straight into MCP responses, and a single invalid byte
     * anywhere in one is fatal in a way that leaves no trace: json_encode()
     * returns false, laravel/mcp's `?: ''` turns that into a zero-length
     * JSON-RPC frame, and the tool call returns nothing while raising nothing.
     * The package's own clipping can no longer produce such a byte, but a note
     * can arrive holding one — a bad paste, or a file another tool wrote as
     * latin-1 — so the bytes are scrubbed where they enter, and the broken
     * note is named in the log rather than silently swallowing a whole answer.
     */
    private function utf8(string $pathname): string
    {
        $contents = File::get($pathname);

        if (mb_check_encoding($contents, 'UTF-8')) {
            return $contents;
        }

        Log::warning(sprintf('Etruscan: note %s is not valid UTF-8; invalid bytes were scrubbed before serving.', $pathname));

        return mb_scrub($contents);
    }

    /**
     * App-relative where possible, matching the convention the `source`
     * frontmatter key already uses, so a path a tool prints is a path a
     * reader can open.
     */
    private function relativePath(string $pathname): string
    {
        $base = base_path().DIRECTORY_SEPARATOR;

        return str_starts_with($pathname, $base)
            ? substr($pathname, strlen($base))
            : $pathname;
    }
}
