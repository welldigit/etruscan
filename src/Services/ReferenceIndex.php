<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Payloads\ScannedClass;
use WellDigit\Etruscan\Utilities\EtruscanConfig;
use WellDigit\Etruscan\Utilities\ReportsDirectoryPreparer;

/**
 * @phpstan-type IndexedReference array{caller: string, alias: string|null, target: string, source: string, kind: string, line: int}
 * @phpstan-type IndexData array{version: int, roots: list<string>, hashes: array<string, string>, issues: list<string>, orphans: list<string>, marker: list<string>, structure: string, references: list<IndexedReference>}
 * @phpstan-type EvidenceLookup array{byTarget: array<string, list<IndexedReference>>, byCaller: array<string, list<IndexedReference>>}
 */
#[\EtruscanNode('reference-index')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('scan')]
final class ReferenceIndex
{
    public const int VERSION = 2;

    public const string FILE_NAME = 'reference-index.json';

    /**
     * The last decoded index, keyed by the file's identity. `write()` goes
     * through File::replace, a rename, so every rewrite carries a new inode and
     * a memo can never outlive the file it was read from. That is the one
     * assumption: an outside edit in place that keeps size and mtime would be
     * missed. Acceptable for a machine-local file only this class writes —
     * and the binding is scoped, which in the long-lived stdio MCP server
     * means the memo lives as long as the process.
     *
     * @var array{key: string, data: IndexData|null, lookup: EvidenceLookup|null}|null
     */
    private ?array $memo = null;

    public function __construct(private readonly VaultReader $vaultReader) {}

    /**
     * @param  list<ScannedClass>  $classes
     * @param  list<string>  $roots
     * @param  array<string, string>  $hashes
     * @param  list<string>  $issues  Scan diagnostics: anything that makes the evidence itself incomplete.
     * @param  list<string>  $orphans  Retained generated notes with no annotated class — reported, never a gate on evidence.
     */
    public function write(string $vaultPath, array $classes, array $roots, array $hashes, array $issues, array $orphans = []): void
    {
        $references = [];

        foreach ($classes as $class) {
            foreach ($class->evidence as $evidence) {
                if (strcasecmp($class->fqcn, $evidence->target) === 0) {
                    continue;
                }

                $references[] = [
                    'caller' => $class->fqcn,
                    'alias' => $class->alias,
                    'target' => $evidence->target,
                    'source' => $class->sourcePath,
                    'kind' => $evidence->kind,
                    'line' => $evidence->line,
                ];
            }
        }

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                $hashes[$root] = 'missing-root';
            }
        }

        ksort($hashes);
        sort($roots);
        usort($references, static fn (array $a, array $b): int => [$a['source'], $a['line'], $a['target']] <=> [$b['source'], $b['line'], $b['target']]);

        $data = [
            'version' => self::VERSION,
            'roots' => $roots,
            'hashes' => $hashes,
            'issues' => $issues,
            'orphans' => $orphans,
            'marker' => [EtruscanConfig::markerKey(), EtruscanConfig::markerValue()],
            'structure' => $this->structureHash($vaultPath),
            'references' => $references,
        ];

        ReportsDirectoryPreparer::prepare($vaultPath.'/.reports');
        File::replace($this->path($vaultPath), json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n", 0666 & ~umask());
    }

    /** @return IndexData|null */
    public function read(string $vaultPath): ?array
    {
        $path = $this->path($vaultPath);

        if (! File::isFile($path)) {
            return null;
        }

        clearstatcache(true, $path);
        $key = $path.'|'.filemtime($path).'|'.filesize($path).'|'.fileinode($path);

        if ($this->memo === null || $this->memo['key'] !== $key) {
            $this->memo = ['key' => $key, 'data' => $this->decode($path), 'lookup' => null];
        }

        return $this->memo['data'];
    }

    /**
     * Evidence rows keyed by lower-cased FQCN on each side, built once per
     * decoded index so a trace is a lookup rather than a pass over every row.
     *
     * @return EvidenceLookup|null
     */
    public function lookup(string $vaultPath): ?array
    {
        $data = $this->read($vaultPath);

        if ($data === null || $this->memo === null) {
            return null;
        }

        if ($this->memo['lookup'] === null) {
            $lookup = ['byTarget' => [], 'byCaller' => []];

            foreach ($data['references'] as $row) {
                $lookup['byTarget'][strtolower($row['target'])][] = $row;
                $lookup['byCaller'][strtolower($row['caller'])][] = $row;
            }

            $this->memo['lookup'] = $lookup;
        }

        return $this->memo['lookup'];
    }

    /** @return IndexData|null */
    private function decode(string $path): ?array
    {
        $data = json_decode(File::get($path), true);

        if (! is_array($data) || ($data['version'] ?? null) !== self::VERSION
            || ! $this->strings($data['roots'] ?? null)
            || ! $this->strings($data['hashes'] ?? null)
            || ! $this->strings($data['issues'] ?? null)
            || ! $this->strings($data['orphans'] ?? null)
            || ! $this->strings($data['marker'] ?? null)
            || ! is_string($data['structure'] ?? null)
            || ! is_array($data['references'] ?? null)) {
            return null;
        }

        foreach ($data['references'] as $row) {
            if (! is_array($row) || ! array_key_exists('alias', $row)
                || ($row['alias'] !== null && ! is_string($row['alias']))
                || ! is_int($row['line'] ?? null) || $row['line'] < 1) {
                return null;
            }

            foreach (['caller', 'target', 'source', 'kind'] as $key) {
                if (! is_string($row[$key] ?? null)) {
                    return null;
                }
            }
        }

        /** @var IndexData $data */
        return $data;
    }

    public function structureHash(string $vaultPath): string
    {
        return self::structureHashOf(($this->vaultReader)($vaultPath, EtruscanConfig::markerKey()));
    }

    /**
     * The hash over generated structure only — frontmatter and both reference
     * lists. Human prose is left out, so editing a description never marks the
     * map stale.
     *
     * @param  array<string, ParsedNote>  $notesByAlias
     */
    public static function structureHashOf(array $notesByAlias): string
    {
        $structure = [];

        foreach ($notesByAlias as $alias => $note) {
            $frontmatter = $note->frontmatter;
            ksort($frontmatter);
            $structure[$alias] = [$frontmatter, $note->links, $note->referencedBy];
        }

        ksort($structure);

        return hash('sha256', json_encode($structure, JSON_THROW_ON_ERROR));
    }

    private function strings(mixed $value): bool
    {
        return is_array($value) && array_all($value, static fn (mixed $item): bool => is_string($item));
    }

    private function path(string $vaultPath): string
    {
        return $vaultPath.'/.reports/'.self::FILE_NAME;
    }
}
