<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Enums\IdentityFrontmatterKey;
use WellDigit\Etruscan\Exceptions\AliasCollisionException;
use WellDigit\Etruscan\Exceptions\ReservedAxisKeyException;
use WellDigit\Etruscan\Payloads\NoteContent;
use WellDigit\Etruscan\Payloads\ScannedClass;

#[\EtruscanNode('node-graph-builder')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('projection')]
final readonly class NodeGraphBuilder
{
    /**
     * @param  list<ScannedClass>  $scannedClasses
     * @param  array<string, string>  $descriptionsByAlias  Human-written descriptions read from the vault,
     *                                                      keyed by alias — the notes are their only source.
     * @return list<NoteContent>
     */
    public function __invoke(array $scannedClasses, array $descriptionsByAlias = []): array
    {
        $aliasByFqcn = $this->mapAliasesByFqcn($scannedClasses);

        $nodes = array_values(array_filter($scannedClasses, static fn (ScannedClass $scannedClass): bool => $scannedClass->isNode()));

        $linksByAlias = [];

        foreach ($nodes as $node) {
            $linksByAlias[(string) $node->alias] = $this->resolveLinks(aliasByFqcn: $aliasByFqcn, scannedClass: $node);
        }

        $referencedByAlias = $this->invertLinks($linksByAlias);

        $notes = [];

        foreach ($nodes as $node) {
            $alias = (string) $node->alias;

            $notes[] = new NoteContent(
                alias: $alias,
                frontmatter: $this->buildFrontmatter($node),
                links: $linksByAlias[$alias],
                referencedBy: $referencedByAlias[$alias] ?? [],
                description: $descriptionsByAlias[$alias] ?? null,
            );
        }

        usort($notes, static fn (NoteContent $firstNoteContent, NoteContent $secondNoteContent): int => strcmp(
            $firstNoteContent->alias,
            $secondNoteContent->alias,
        ));

        return $notes;
    }

    /**
     * @param  array<string, list<string>>  $linksByAlias
     * @return array<string, list<string>>
     */
    private function invertLinks(array $linksByAlias): array
    {
        $referencedByAlias = [];

        foreach ($linksByAlias as $alias => $links) {
            foreach ($links as $link) {
                $referencedByAlias[$link][] = $alias;
            }
        }

        foreach ($referencedByAlias as $link => $aliases) {
            sort($aliases);
            $referencedByAlias[$link] = $aliases;
        }

        return $referencedByAlias;
    }

    /**
     * @param  list<ScannedClass>  $scannedClasses
     * @return array<string, string> FQCN => alias
     */
    private function mapAliasesByFqcn(array $scannedClasses): array
    {
        $aliasByFqcn = [];
        $fqcnByAlias = [];

        foreach ($scannedClasses as $scannedClass) {
            if (! $scannedClass->isNode()) {
                continue;
            }

            $alias = (string) $scannedClass->alias;

            if (isset($fqcnByAlias[$alias]) && $fqcnByAlias[$alias] !== $scannedClass->fqcn) {
                throw AliasCollisionException::make(
                    alias: $alias,
                    firstFqcn: $fqcnByAlias[$alias],
                    secondFqcn: $scannedClass->fqcn,
                );
            }

            $aliasByFqcn[strtolower($scannedClass->fqcn)] = $alias;
            $fqcnByAlias[$alias] = $scannedClass->fqcn;
        }

        return $aliasByFqcn;
    }

    /**
     * @return array<string, string|list<string>>
     */
    private function buildFrontmatter(ScannedClass $scannedClass): array
    {
        $frontmatter = [
            IdentityFrontmatterKey::Alias->value => (string) $scannedClass->alias,
            IdentityFrontmatterKey::ClassShortName->value => $this->resolveShortName($scannedClass->fqcn),
            IdentityFrontmatterKey::Fqcn->value => $scannedClass->fqcn,
        ];

        if ($scannedClass->extendsFqcn !== null) {
            $frontmatter[IdentityFrontmatterKey::Extends->value] = $scannedClass->extendsFqcn;
        }

        $frontmatter[IdentityFrontmatterKey::Source->value] = $this->relativizeSourcePath($scannedClass->sourcePath);

        foreach ($scannedClass->axes as $key => $values) {
            if (IdentityFrontmatterKey::isReserved($key)) {
                throw ReservedAxisKeyException::make(alias: (string) $scannedClass->alias, axisKey: $key);
            }

            $frontmatter[$key] = count($values) === 1 ? $values[0] : $values;
        }

        return $frontmatter;
    }

    private function resolveShortName(string $fqcn): string
    {
        $position = strrpos($fqcn, '\\');

        return $position === false ? $fqcn : substr($fqcn, $position + 1);
    }

    private function relativizeSourcePath(string $sourcePath): string
    {
        foreach ([base_path(), getcwd()] as $rootPath) {
            if (! is_string($rootPath) || $rootPath === '') {
                continue;
            }

            $rootPath .= DIRECTORY_SEPARATOR;

            if (str_starts_with($sourcePath, $rootPath)) {
                return substr($sourcePath, strlen($rootPath));
            }
        }

        return $sourcePath;
    }

    /**
     * @param  array<string, string>  $aliasByFqcn
     * @return list<string>
     */
    private function resolveLinks(array $aliasByFqcn, ScannedClass $scannedClass): array
    {
        $aliases = [];

        foreach ($scannedClass->references as $reference) {
            $reference = strtolower(ltrim($reference, '\\'));

            if ($reference === strtolower($scannedClass->fqcn)) {
                continue;
            }

            if (isset($aliasByFqcn[$reference])) {
                $aliases[$aliasByFqcn[$reference]] = true;
            }
        }

        $links = array_keys($aliases);
        sort($links);

        return $links;
    }
}
