<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Enums\NoteSection;
use WellDigit\Etruscan\Payloads\NoteContent;
use WellDigit\Etruscan\Utilities\BlankLineTrimmer;

#[\EtruscanNode('markdown-note-renderer')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('projection')]
final readonly class MarkdownNoteRenderer
{
    private const int DESCRIPTION_WIDTH = 100;

    /**
     * The Description section is human-owned: generation always seeds its heading —
     * an empty slot inviting the human words — but the text under it is only ever
     * carried over from the existing note (keyed by alias), never derived or rewritten.
     * Plain prose is tidy-wrapped to a readable width; text carrying its own
     * formatting passes through byte-for-byte (see wrapDescription).
     */
    public function __invoke(NoteContent $noteContent, string $markerKey, string $markerValue, string $manualContent = '', string $carriedDescription = ''): string
    {
        $frontmatter = $noteContent->frontmatter;
        $frontmatter[$markerKey] = $markerValue;

        $lines = ['---'];

        foreach ($frontmatter as $key => $value) {
            if (is_array($value)) {
                $lines[] = $key.':';

                foreach ($value as $item) {
                    $lines[] = '  - '.$this->renderScalar($item);
                }

                continue;
            }

            $lines[] = $key.': '.$this->renderScalar($value);
        }

        $lines[] = '---';
        $lines[] = '';

        $description = BlankLineTrimmer::trim($carriedDescription);

        $lines[] = NoteSection::Description->heading();
        $lines[] = '';

        if ($description !== '') {
            $lines[] = $this->wrapDescription($description);
            $lines[] = '';
        }

        if ($noteContent->links !== []) {
            $lines[] = NoteSection::References->heading();
            $lines[] = '';

            foreach ($noteContent->links as $link) {
                $lines[] = '- [['.$link.']]';
            }

            $lines[] = '';
        }

        if ($noteContent->referencedBy !== []) {
            $lines[] = NoteSection::ReferencedBy->heading();
            $lines[] = '';

            foreach ($noteContent->referencedBy as $referrer) {
                $lines[] = '- [['.$referrer.']]';
            }
        }

        $markdown = rtrim(implode("\n", $lines))."\n";

        $manual = BlankLineTrimmer::trim($manualContent);

        if ($manual !== '') {
            $markdown .= "\n".$manual."\n";
        }

        return $markdown;
    }

    /**
     * A description that carries its own structure — lists, code fences, headings,
     * quotes, tables, indentation — is the human's formatting and passes through
     * byte-for-byte, first line included: the edges are trimmed by line, never by
     * character, so an indented opening stays indented. Only pure prose is reflowed:
     * each blank-line-separated paragraph collapses to a logical line and re-wraps
     * into clean, even lines. Both paths are idempotent across regenerations.
     */
    private function wrapDescription(string $description): string
    {
        $description = BlankLineTrimmer::trim($description);

        if ($this->hasStructuredLines($description)) {
            return implode("\n", array_map(rtrim(...), preg_split('/\R/', $description) ?: []));
        }

        $paragraphs = preg_split('/\R{2,}/', $description) ?: [];

        $wrapped = array_map(
            fn (string $paragraph): string => wordwrap(
                (string) preg_replace('/\s+/', ' ', trim($paragraph)),
                self::DESCRIPTION_WIDTH,
                "\n",
                false,
            ),
            $paragraphs,
        );

        return implode("\n\n", $wrapped);
    }

    private function hasStructuredLines(string $description): bool
    {
        foreach (preg_split('/\R/', $description) ?: [] as $line) {
            $unindented = ltrim($line);

            if ($unindented === '') {
                continue;
            }

            if ($unindented !== $line) {
                return true;
            }

            if (preg_match('/^(```|~~~|[-*+] |#|>|\||\d+[.)] )/', $unindented) === 1) {
                return true;
            }
        }

        return false;
    }

    private function renderScalar(string $value): string
    {
        if ($value === '' || preg_match('/[:#\[\]{}",&*!|>\'%@`]/', $value) === 1 || trim($value) !== $value) {
            return '"'.addcslashes($value, '"\\').'"';
        }

        return $value;
    }
}
