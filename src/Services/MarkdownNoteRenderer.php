<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\GeneratedNoteSection;
use WellDigit\Etruscan\Payloads\NoteContent;

#[EtruscanNode('markdown-note-renderer')]
#[EtruscanLayer('service')]
#[EtruscanContext('projection')]
final readonly class MarkdownNoteRenderer
{
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

        $description = $noteContent->description !== null && $noteContent->description !== ''
            ? $noteContent->description
            : trim($carriedDescription);

        if ($description !== '') {
            $lines[] = GeneratedNoteSection::Description->heading();
            $lines[] = '';
            $lines[] = $description;
            $lines[] = '';
        }

        if ($noteContent->links !== []) {
            $lines[] = GeneratedNoteSection::References->heading();
            $lines[] = '';

            foreach ($noteContent->links as $link) {
                $lines[] = '- [['.$link.']]';
            }
        }

        $markdown = rtrim(implode("\n", $lines))."\n";

        if (trim($manualContent) !== '') {
            $markdown .= "\n".trim($manualContent)."\n";
        }

        return $markdown;
    }

    private function renderScalar(string $value): string
    {
        if ($value === '' || preg_match('/[:#\[\]{}",&*!|>\'%@`]/', $value) === 1 || trim($value) !== $value) {
            return '"'.addcslashes($value, '"\\').'"';
        }

        return $value;
    }
}
