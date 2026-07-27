<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\GeneratedNoteSection;
use WellDigit\Etruscan\Enums\IdentityFrontmatterKey;
use WellDigit\Etruscan\Payloads\ParsedNote;

#[EtruscanNode('note-parser')]
#[EtruscanLayer('service')]
#[EtruscanContext('vault')]
final readonly class NoteParser
{
    private const string LEGACY_MANUAL_DELIMITER = '%% Manual notes below this line are preserved across regenerations %%';

    public function __invoke(string $content): ParsedNote
    {
        $frontmatter = $this->parseFrontmatter($content);
        $sections = $this->parseBody($this->stripFrontmatter($content));

        $alias = $frontmatter[IdentityFrontmatterKey::Alias->value] ?? null;

        return new ParsedNote(
            alias: is_string($alias) ? $alias : null,
            frontmatter: $frontmatter,
            description: $sections['description'],
            manual: $sections['manual'],
            links: $sections['links'],
            referencedBy: $sections['referencedBy'],
        );
    }

    /**
     * @return array<string, string|list<string>>
     */
    private function parseFrontmatter(string $content): array
    {
        $raw = $this->extractFrontmatter($content);

        if ($raw === null) {
            return [];
        }

        $frontmatter = [];
        $currentKey = null;

        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            if (preg_match('/^([A-Za-z0-9_-]+):\s*(.*)$/', $line, $matches) === 1) {
                $key = $matches[1];
                $value = trim($matches[2]);

                if ($value === '') {
                    $frontmatter[$key] = [];
                    $currentKey = $key;

                    continue;
                }

                $frontmatter[$key] = $this->unquote($value);
                $currentKey = null;

                continue;
            }

            if ($currentKey !== null && preg_match('/^\s+-\s+(.*)$/', $line, $matches) === 1) {
                /** @var list<string> $items */
                $items = is_array($frontmatter[$currentKey]) ? $frontmatter[$currentKey] : [];
                $items[] = $this->unquote(trim($matches[1]));
                $frontmatter[$currentKey] = $items;
            }
        }

        return $frontmatter;
    }

    /**
     * @return array{description: string, manual: string, links: list<string>, referencedBy: list<string>}
     */
    private function parseBody(string $body): array
    {
        $descriptionLines = [];
        $manualLines = [];
        $links = [];
        $referencedBy = [];
        $section = null;

        foreach (preg_split('/\R/', $body) ?: [] as $line) {
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

            if ($trimmed === GeneratedNoteSection::ReferencedBy->heading()) {
                $section = GeneratedNoteSection::ReferencedBy;

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

            if ($section === GeneratedNoteSection::References || $section === GeneratedNoteSection::ReferencedBy) {
                if ($trimmed === '') {
                    continue;
                }

                if (str_starts_with($trimmed, '- [[')) {
                    if (preg_match('/^- \[\[([^\]]+)\]\]$/', $trimmed, $matches) === 1) {
                        if ($section === GeneratedNoteSection::References) {
                            $links[] = $matches[1];
                        } else {
                            $referencedBy[] = $matches[1];
                        }
                    }

                    continue;
                }

                $section = null;
            }

            $manualLines[] = $line;
        }

        return [
            'description' => trim(implode("\n", $descriptionLines)),
            'manual' => trim(implode("\n", $manualLines)),
            'links' => $links,
            'referencedBy' => $referencedBy,
        ];
    }

    private function extractFrontmatter(string $content): ?string
    {
        if (! str_starts_with($content, '---')) {
            return null;
        }

        $end = strpos($content, "\n---", 3);

        return $end === false ? $content : substr($content, 0, $end);
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

    private function unquote(string $value): string
    {
        if (strlen($value) >= 2 && str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return stripcslashes(substr($value, 1, -1));
        }

        return $value;
    }
}
