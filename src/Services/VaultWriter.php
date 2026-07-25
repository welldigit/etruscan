<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use FilesystemIterator;
use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\GeneratedNoteSection;
use WellDigit\Etruscan\Enums\IdentityFrontmatterKey;
use WellDigit\Etruscan\Payloads\NoteContent;

#[EtruscanNode('vault-writer')]
#[EtruscanLayer('service')]
#[EtruscanContext('vault')]
final readonly class VaultWriter
{
    private const string LEGACY_MANUAL_DELIMITER = '%% Manual notes below this line are preserved across regenerations %%';

    public function __construct(
        private MarkdownNoteRenderer $markdownNoteRenderer,
    ) {}

    /**
     * @param  list<NoteContent>  $notes
     * @return array{written: int, removed: int, orphaned: list<string>}
     */
    public function __invoke(
        string $vaultPath,
        array $notes,
        NotePathResolver $notePathResolver,
        string $markerKey,
        string $markerValue,
        bool $purgeOrphans = false,
    ): array {
        File::ensureDirectoryExists($vaultPath);

        $existingNotes = $this->indexExistingNotes(markerKey: $markerKey, vaultPath: $vaultPath);
        $carriedByAlias = $this->mapCarriedContentByAlias($existingNotes);

        $expectedPaths = [];
        $expectedAliases = [];
        $written = 0;

        foreach ($notes as $noteContent) {
            $relativePath = $notePathResolver($noteContent);
            $absolutePath = $vaultPath.DIRECTORY_SEPARATOR.$relativePath;

            $carried = $carriedByAlias[$noteContent->alias] ?? ['description' => '', 'manual' => ''];

            File::ensureDirectoryExists(dirname($absolutePath));
            File::put($absolutePath, ($this->markdownNoteRenderer)(
                noteContent: $noteContent,
                markerKey: $markerKey,
                markerValue: $markerValue,
                manualContent: $carried['manual'],
                carriedDescription: $carried['description'],
            ));

            $expectedPaths[$relativePath] = true;
            $expectedAliases[$noteContent->alias] = true;
            $written++;
        }

        [$removed, $orphaned] = $this->sweepStale(
            existingNotes: $existingNotes,
            expectedAliases: $expectedAliases,
            expectedPaths: $expectedPaths,
            purgeOrphans: $purgeOrphans,
            vaultPath: $vaultPath,
        );

        $this->pruneEmptyDirectories($vaultPath);

        return ['written' => $written, 'removed' => $removed, 'orphaned' => $orphaned];
    }

    /**
     * @return list<array{relativePath: string, alias: string|null, description: string, manualContent: string}>
     */
    private function indexExistingNotes(string $markerKey, string $vaultPath): array
    {
        $index = [];

        foreach (File::allFiles($vaultPath) as $existingFile) {
            if ($existingFile->getExtension() !== 'md') {
                continue;
            }

            $content = File::get($existingFile->getPathname());

            if (! $this->isGenerated(content: $content, markerKey: $markerKey)) {
                continue;
            }

            $carriedContent = $this->extractCarriedContent($content);

            $index[] = [
                'relativePath' => str_replace(DIRECTORY_SEPARATOR, '/', $existingFile->getRelativePathname()),
                'alias' => $this->extractAlias($content),
                'description' => $carriedContent['description'],
                'manualContent' => $carriedContent['manual'],
            ];
        }

        return $index;
    }

    /**
     * @param  list<array{relativePath: string, alias: string|null, description: string, manualContent: string}>  $existingNotes
     * @return array<string, array{description: string, manual: string}>
     */
    private function mapCarriedContentByAlias(array $existingNotes): array
    {
        $carriedByAlias = [];

        foreach ($existingNotes as $existingNote) {
            if ($existingNote['alias'] === null) {
                continue;
            }

            $carried = $carriedByAlias[$existingNote['alias']] ?? ['description' => '', 'manual' => ''];

            if ($carried['description'] === '' && $carried['manual'] === '') {
                $carriedByAlias[$existingNote['alias']] = [
                    'description' => $existingNote['description'],
                    'manual' => $existingNote['manualContent'],
                ];
            }
        }

        return $carriedByAlias;
    }

    /**
     * @param  list<array{relativePath: string, alias: string|null, description: string, manualContent: string}>  $existingNotes
     * @param  array<string, true>  $expectedAliases
     * @param  array<string, true>  $expectedPaths
     * @return array{0: int, 1: list<string>}
     */
    private function sweepStale(
        array $existingNotes,
        array $expectedAliases,
        array $expectedPaths,
        bool $purgeOrphans,
        string $vaultPath,
    ): array {
        $removed = 0;
        $orphaned = [];

        foreach ($existingNotes as $existingNote) {
            if (isset($expectedPaths[$existingNote['relativePath']])) {
                continue;
            }

            $isRelocated = $existingNote['alias'] !== null && isset($expectedAliases[$existingNote['alias']]);
            $isPristine = $existingNote['manualContent'] === '' && $existingNote['description'] === '';

            if ($isRelocated || $purgeOrphans || $isPristine) {
                File::delete($vaultPath.DIRECTORY_SEPARATOR.$existingNote['relativePath']);
                $removed++;

                continue;
            }

            $orphaned[] = $existingNote['relativePath'];
        }

        return [$removed, $orphaned];
    }

    private function pruneEmptyDirectories(string $directoryPath): void
    {
        foreach (File::directories($directoryPath) as $childDirectory) {
            $this->pruneEmptyDirectories($childDirectory);

            if (! new FilesystemIterator($childDirectory)->valid()) {
                File::deleteDirectory($childDirectory);
            }
        }
    }

    private function isGenerated(string $content, string $markerKey): bool
    {
        $frontmatter = $this->extractFrontmatter($content);

        return $frontmatter !== null
            && preg_match('/^'.preg_quote($markerKey, '/').':\s/m', $frontmatter) === 1;
    }

    private function extractFrontmatter(string $content): ?string
    {
        if (! str_starts_with($content, '---')) {
            return null;
        }

        $end = strpos($content, "\n---", 3);

        return $end === false ? $content : substr($content, 0, $end);
    }

    private function extractAlias(string $content): ?string
    {
        $frontmatter = $this->extractFrontmatter($content);

        if ($frontmatter === null
            || preg_match('/^'.IdentityFrontmatterKey::Alias->value.':\s*(.+?)\s*$/m', $frontmatter, $matches) !== 1) {
            return null;
        }

        $alias = $matches[1];

        if (strlen($alias) >= 2 && str_starts_with($alias, '"') && str_ends_with($alias, '"')) {
            return stripcslashes(substr($alias, 1, -1));
        }

        return $alias;
    }

    /**
     * @return array{description: string, manual: string}
     */
    private function extractCarriedContent(string $content): array
    {
        $descriptionLines = [];
        $manualLines = [];
        $section = null;

        foreach (preg_split('/\R/', $this->stripFrontmatter($content)) ?: [] as $line) {
            $trimmed = rtrim($line);

            if ($trimmed === self::LEGACY_MANUAL_DELIMITER) {
                continue;
            }

            if ($trimmed === GeneratedNoteSection::Description->heading()) {
                $section = GeneratedNoteSection::Description;

                continue;
            }

            if ($trimmed === GeneratedNoteSection::References->heading()) {
                $section = GeneratedNoteSection::References;

                continue;
            }

            if ($section === GeneratedNoteSection::Description) {
                if (str_starts_with($trimmed, '## ')) {
                    $section = null;
                } else {
                    $descriptionLines[] = $line;

                    continue;
                }
            }

            if ($section === GeneratedNoteSection::References) {
                if ($trimmed === '' || str_starts_with($trimmed, '- [[')) {
                    continue;
                }

                $section = null;
            }

            $manualLines[] = $line;
        }

        return [
            'description' => trim(implode("\n", $descriptionLines)),
            'manual' => trim(implode("\n", $manualLines)),
        ];
    }

    private function stripFrontmatter(string $content): string
    {
        if (! str_starts_with($content, '---')) {
            return $content;
        }

        $end = strpos($content, "\n---", 3);

        if ($end === false) {
            return '';
        }

        $afterClosingFence = strpos($content, "\n", $end + 1);

        return $afterClosingFence === false ? '' : substr($content, $afterClosingFence + 1);
    }
}
