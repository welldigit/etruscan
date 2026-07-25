<?php

declare(strict_types=1);

use WellDigit\Etruscan\Payloads\NoteContent;
use WellDigit\Etruscan\Services\MarkdownNoteRenderer;

test('it renders frontmatter, the generation marker and wikilink references', function () {
    $noteContent = new NoteContent(
        alias: 'monitor-query',
        frontmatter: ['alias' => 'monitor-query', 'layer' => 'query'],
        links: ['monitor', 'team'],
    );

    $markdown = (new MarkdownNoteRenderer)(
        noteContent: $noteContent,
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($markdown)->toBe(implode("\n", [
        '---',
        'alias: monitor-query',
        'layer: query',
        'ci-generated: barkme',
        '---',
        '',
        '## References',
        '',
        '- [[monitor]]',
        '- [[team]]',
    ])."\n");
});

test('multi-value axes render as YAML lists', function () {
    $noteContent = new NoteContent(
        alias: 'monitor',
        frontmatter: ['alias' => 'monitor', 'context' => ['monitor', 'incident']],
        links: [],
    );

    $markdown = (new MarkdownNoteRenderer)(noteContent: $noteContent, markerKey: 'ci', markerValue: 'x');

    expect($markdown)->toContain("context:\n  - monitor\n  - incident\n");
});

test('a note without links omits the references section entirely', function () {
    $noteContent = new NoteContent(alias: 'leaf', frontmatter: ['alias' => 'leaf'], links: []);

    $markdown = (new MarkdownNoteRenderer)(noteContent: $noteContent, markerKey: 'ci', markerValue: 'x');

    expect($markdown)->toBe("---\nalias: leaf\nci: x\n---\n")
        ->and($markdown)->not->toContain('## References');
});

test('a description renders as its own section above the references', function () {
    $noteContent = new NoteContent(
        alias: 'monitor',
        frontmatter: ['alias' => 'monitor'],
        links: ['team'],
        description: 'Watches endpoints for downtime.',
    );

    $markdown = (new MarkdownNoteRenderer)(noteContent: $noteContent, markerKey: 'ci', markerValue: 'x');

    expect($markdown)->toBe(implode("\n", [
        '---',
        'alias: monitor',
        'ci: x',
        '---',
        '',
        '## Description',
        '',
        'Watches endpoints for downtime.',
        '',
        '## References',
        '',
        '- [[team]]',
    ])."\n");
});

test('a note without a description omits the section entirely', function () {
    $noteContent = new NoteContent(alias: 'leaf', frontmatter: ['alias' => 'leaf'], links: []);

    $markdown = (new MarkdownNoteRenderer)(noteContent: $noteContent, markerKey: 'ci', markerValue: 'x');

    expect($markdown)->not->toContain('## Description');
});

test('manual content is re-appended below the generated blocks', function () {
    $noteContent = new NoteContent(alias: 'leaf', frontmatter: ['alias' => 'leaf'], links: ['team']);

    $markdown = (new MarkdownNoteRenderer)(
        noteContent: $noteContent,
        markerKey: 'ci',
        markerValue: 'x',
        manualContent: "My hard-won insight.\n",
    );

    expect($markdown)->toEndWith("- [[team]]\n\nMy hard-won insight.\n");
});

test('blank manual content adds nothing', function () {
    $noteContent = new NoteContent(alias: 'leaf', frontmatter: ['alias' => 'leaf'], links: []);

    $markdown = (new MarkdownNoteRenderer)(
        noteContent: $noteContent,
        markerKey: 'ci',
        markerValue: 'x',
        manualContent: "  \n\n",
    );

    expect($markdown)->toBe("---\nalias: leaf\nci: x\n---\n");
});

test('ambiguous YAML scalars are quoted and escaped', function () {
    $noteContent = new NoteContent(
        alias: 'tricky',
        frontmatter: [
            'alias' => 'tricky',
            'colon' => 'key: value',
            'quote' => 'say "hi"',
            'empty' => '',
            'padded' => ' spaced ',
        ],
        links: [],
    );

    $markdown = (new MarkdownNoteRenderer)(noteContent: $noteContent, markerKey: 'ci', markerValue: 'x');

    expect($markdown)->toContain('colon: "key: value"')
        ->and($markdown)->toContain('quote: "say \\"hi\\""')
        ->and($markdown)->toContain('empty: ""')
        ->and($markdown)->toContain('padded: " spaced "');
});

test('plain slug values stay unquoted', function () {
    $noteContent = new NoteContent(alias: 'plain', frontmatter: ['alias' => 'plain-slug_1'], links: []);

    $markdown = (new MarkdownNoteRenderer)(noteContent: $noteContent, markerKey: 'ci', markerValue: 'x');

    expect($markdown)->toContain('alias: plain-slug_1')
        ->not->toContain('"plain-slug_1"');
});

test('the carried description is rendered only when the class provides none', function () {
    $noteContent = new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha'], links: []);

    $withCarried = (new MarkdownNoteRenderer)(
        noteContent: $noteContent,
        markerKey: 'ci',
        markerValue: 'x',
        carriedDescription: 'Human words.',
    );

    $withBoth = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha'], links: [], description: 'Docblock words.'),
        markerKey: 'ci',
        markerValue: 'x',
        carriedDescription: 'Human words.',
    );

    expect($withCarried)->toContain("## Description\n\nHuman words.")
        ->and($withBoth)->toContain('Docblock words.')
        ->and($withBoth)->not->toContain('Human words.');
});

test('the output is deterministic for identical input', function () {
    $noteContent = new NoteContent(
        alias: 'stable',
        frontmatter: ['alias' => 'stable', 'layer' => 'action'],
        links: ['a', 'b'],
    );

    $markdownNoteRenderer = new MarkdownNoteRenderer;

    expect($markdownNoteRenderer(noteContent: $noteContent, markerKey: 'ci', markerValue: 'x'))
        ->toBe($markdownNoteRenderer(noteContent: $noteContent, markerKey: 'ci', markerValue: 'x'));
});
