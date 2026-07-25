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

test('inbound links render as a Referenced by section below the references', function () {
    $markdown = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(
            alias: 'team',
            frontmatter: ['alias' => 'team'],
            links: ['team-invite'],
            referencedBy: ['incident', 'monitor'],
        ),
        markerKey: 'ci',
        markerValue: 'x',
    );

    expect($markdown)->toContain("## References\n\n- [[team-invite]]\n\n## Referenced by\n\n- [[incident]]\n- [[monitor]]")
        ->and(strpos($markdown, '## References'))->toBeLessThan((int) strpos($markdown, '## Referenced by'));
});

test('a note with no inbound links omits the Referenced by section', function () {
    $markdown = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'leaf', frontmatter: ['alias' => 'leaf'], links: ['root'], referencedBy: []),
        markerKey: 'ci',
        markerValue: 'x',
    );

    expect($markdown)->toContain('## References')
        ->and($markdown)->not->toContain('## Referenced by');
});

test('a note without a description omits the section entirely', function () {
    $noteContent = new NoteContent(alias: 'leaf', frontmatter: ['alias' => 'leaf'], links: []);

    $markdown = (new MarkdownNoteRenderer)(noteContent: $noteContent, markerKey: 'ci', markerValue: 'x');

    expect($markdown)->not->toContain('## Description');
});

test('a long single-line description is wrapped into readable lines without losing a word', function () {
    $sentence = trim(str_repeat('lorem ipsum dolor sit amet ', 12)); // one line, well over 100 chars

    $markdown = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'wide', frontmatter: ['alias' => 'wide'], links: [], description: $sentence),
        markerKey: 'ci',
        markerValue: 'x',
    );

    $descriptionBlock = trim(explode("\n\n## References", explode("## Description\n\n", $markdown)[1])[0]);
    $descriptionLines = explode("\n", $descriptionBlock);

    expect(count($descriptionLines))->toBeGreaterThan(1)                                      // it wrapped
        ->and(collect($descriptionLines)->every(fn (string $line): bool => mb_strlen($line) <= 100))->toBeTrue()
        ->and(implode(' ', $descriptionLines))->toBe($sentence);                              // no word cut or lost
});

test('an unevenly hand-wrapped description is reflowed into clean, even lines', function () {
    // simulates a note whose description was hand-edited, leaving ragged line breaks
    $ragged = "Owns the write-and-sweep\ncycle: rewrites generated\nnotes, carries manual content across regenerations and folder moves, and prunes empty folders.";

    $markdown = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'x', frontmatter: ['alias' => 'x'], links: [], description: $ragged),
        markerKey: 'ci',
        markerValue: 'x',
    );

    $descriptionBlock = trim(explode("## Description\n\n", $markdown)[1]);
    $lines = explode("\n", $descriptionBlock);

    // reflowed: every line packs up to the width (only the last may be short)
    expect(collect($lines)->every(fn (string $line): bool => mb_strlen($line) <= 100))->toBeTrue()
        ->and(collect(array_slice($lines, 0, -1))->every(fn (string $line): bool => mb_strlen($line) > 50))->toBeTrue()
        ->and(implode(' ', $lines))->toBe((string) preg_replace('/\s+/', ' ', $ragged));
});

test('paragraph breaks in a description are preserved through reflow', function () {
    $twoParagraphs = "First paragraph, short.\n\nSecond paragraph, also short.";

    $markdown = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'x', frontmatter: ['alias' => 'x'], links: [], description: $twoParagraphs),
        markerKey: 'ci',
        markerValue: 'x',
    );

    expect($markdown)->toContain("## Description\n\nFirst paragraph, short.\n\nSecond paragraph, also short.\n");
});

test('a description already within the width is left on its own line', function () {
    $markdown = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'narrow', frontmatter: ['alias' => 'narrow'], links: [], description: 'Short and sweet.'),
        markerKey: 'ci',
        markerValue: 'x',
    );

    expect($markdown)->toContain("## Description\n\nShort and sweet.\n");
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
