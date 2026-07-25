<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\IdentityFrontmatterKey;
use WellDigit\Etruscan\Exceptions\AliasCollisionException;
use WellDigit\Etruscan\Exceptions\ReservedAxisKeyException;
use WellDigit\Etruscan\Payloads\NoteContent;
use WellDigit\Etruscan\Payloads\ScannedClass;

#[EtruscanNode('node-graph-builder')]
#[EtruscanLayer('service')]
#[EtruscanContext('projection')]
final readonly class NodeGraphBuilder
{
    /**
     * @param  list<ScannedClass>  $scannedClasses
     * @return list<NoteContent>
     */
    public function __invoke(array $scannedClasses): array
    {
        $aliasByFqcn = $this->mapAliasesByFqcn($scannedClasses);

        $notes = [];

        foreach ($scannedClasses as $scannedClass) {
            if (! $scannedClass->isNode()) {
                continue;
            }

            $notes[] = new NoteContent(
                alias: (string) $scannedClass->alias,
                frontmatter: $this->buildFrontmatter($scannedClass),
                links: $this->resolveLinks(aliasByFqcn: $aliasByFqcn, scannedClass: $scannedClass),
                description: $scannedClass->description,
            );
        }

        usort($notes, static fn (NoteContent $firstNoteContent, NoteContent $secondNoteContent): int => strcmp(
            $firstNoteContent->alias,
            $secondNoteContent->alias,
        ));

        return $notes;
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

            $aliasByFqcn[$scannedClass->fqcn] = $alias;
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
            $reference = ltrim($reference, '\\');

            if ($reference === $scannedClass->fqcn) {
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
