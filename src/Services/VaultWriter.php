<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use FilesystemIterator;
use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Payloads\NoteContent;

#[\EtruscanNode('vault-writer')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('vault')]
final readonly class VaultWriter
{
    public function __construct(
        private MarkdownNoteRenderer $markdownNoteRenderer,
        private NoteParser $noteParser,
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

            // Written to a temp file and renamed, so a tool reading mid-generation
            // never sees half a note. The mode is explicit: File::replace defaults
            // to 0777 minus umask, which would mark every committed note executable.
            File::replace($absolutePath, ($this->markdownNoteRenderer)(
                noteContent: $noteContent,
                markerKey: $markerKey,
                markerValue: $markerValue,
                manualContent: $carried['manual'],
                carriedDescription: $carried['description'],
            ), 0666 & ~umask());

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

            $parsedNote = ($this->noteParser)(File::get($existingFile->getPathname()));

            if (! array_key_exists($markerKey, $parsedNote->frontmatter)) {
                continue;
            }

            $index[] = [
                'relativePath' => str_replace(DIRECTORY_SEPARATOR, '/', $existingFile->getRelativePathname()),
                'alias' => $parsedNote->alias,
                'description' => $parsedNote->description,
                'manualContent' => $parsedNote->manual,
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
}
