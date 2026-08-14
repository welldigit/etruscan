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
        '## Description',
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

    expect($markdown)->toBe("---\nalias: leaf\nci: x\n---\n\n## Description\n")
        ->and($markdown)->not->toContain('## References');
});

test('a description renders as its own section above the references', function () {
    $noteContent = new NoteContent(
        alias: 'monitor',
        frontmatter: ['alias' => 'monitor'],
        links: ['team'],
    );

    $markdown = (new MarkdownNoteRenderer)(
        noteContent: $noteContent,
        markerKey: 'ci',
        markerValue: 'x',
        carriedDescription: 'Watches endpoints for downtime.',
    );

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

test('a note without a description still gets the empty section — the slot to fill', function () {
    $noteContent = new NoteContent(alias: 'leaf', frontmatter: ['alias' => 'leaf'], links: ['root']);

    $markdown = (new MarkdownNoteRenderer)(noteContent: $noteContent, markerKey: 'ci', markerValue: 'x');

    expect($markdown)->toContain("## Description\n\n## References");
});

test('a long single-line description is wrapped into readable lines without losing a word', function () {
    $sentence = trim(str_repeat('lorem ipsum dolor sit amet ', 12)); // one line, well over 100 chars

    $markdown = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'wide', frontmatter: ['alias' => 'wide'], links: []),
        markerKey: 'ci',
        markerValue: 'x',
        carriedDescription: $sentence,
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
        noteContent: new NoteContent(alias: 'x', frontmatter: ['alias' => 'x'], links: []),
        markerKey: 'ci',
        markerValue: 'x',
        carriedDescription: $ragged,
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
        noteContent: new NoteContent(alias: 'x', frontmatter: ['alias' => 'x'], links: []),
        markerKey: 'ci',
        markerValue: 'x',
        carriedDescription: $twoParagraphs,
    );

    expect($markdown)->toContain("## Description\n\nFirst paragraph, short.\n\nSecond paragraph, also short.\n");
});

test('a bulleted list in a description is carried byte-for-byte, never reflowed', function () {
    $withList = "Enforces the plan cap:\n- guards the quota\n- rejects overdraft\n- logs the refusal";

    $markdown = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'x', frontmatter: ['alias' => 'x'], links: []),
        markerKey: 'ci',
        markerValue: 'x',
        carriedDescription: $withList,
    );

    expect($markdown)->toContain("## Description\n\n".$withList."\n");
});

test('a code fence in a description survives regeneration untouched', function () {
    $withFence = "Emits one JSON line per event:\n\n```\n{\"status\": \"ok\"}\n\n{\"status\": \"miss\"}\n```\n\nBest-effort by design.";

    $markdown = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'x', frontmatter: ['alias' => 'x'], links: []),
        markerKey: 'ci',
        markerValue: 'x',
        carriedDescription: $withFence,
    );

    expect($markdown)->toContain("## Description\n\n".$withFence."\n");
});

test('a structured description is idempotent across renders, even with a ragged prose paragraph', function () {
    // the long prose line would normally reflow — the list opts the whole text out
    $structured = 'A very long opening sentence that runs well past the hundred column mark and would otherwise be rewrapped by the prose path. '
        ."Key rules:\n1. first rule\n2. second rule";

    $markdownNoteRenderer = new MarkdownNoteRenderer;
    $render = fn (string $description): string => $markdownNoteRenderer(
        noteContent: new NoteContent(alias: 'x', frontmatter: ['alias' => 'x'], links: []),
        markerKey: 'ci',
        markerValue: 'x',
        carriedDescription: $description,
    );

    $first = $render($structured);
    $body = trim(explode('## Description', $first)[1]);

    expect($first)->toContain("1. first rule\n2. second rule")
        ->and($render($body))->toBe($first);
});

test('a description already within the width is left on its own line', function () {
    $markdown = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'narrow', frontmatter: ['alias' => 'narrow'], links: []),
        markerKey: 'ci',
        markerValue: 'x',
        carriedDescription: 'Short and sweet.',
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

    expect($markdown)->toBe("---\nalias: leaf\nci: x\n---\n\n## Description\n");
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

test('the description is only ever the carried human text — nothing code-side can override it', function () {
    $withCarried = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha'], links: [], description: 'Code-derived words.'),
        markerKey: 'ci',
        markerValue: 'x',
        carriedDescription: 'Human words.',
    );

    expect($withCarried)->toContain("## Description\n\nHuman words.")
        ->and($withCarried)->not->toContain('Code-derived words.');
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

test('an indented opening line keeps its indentation — the edges are trimmed by line, not by character', function () {
    $indentedBlock = "    \$config = [\n        'retries' => 3,\n    ];";

    $markdown = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'x', frontmatter: ['alias' => 'x'], links: []),
        markerKey: 'ci',
        markerValue: 'x',
        carriedDescription: $indentedBlock,
    );

    expect($markdown)->toContain("## Description\n\n".$indentedBlock."\n");
});

test('blank lines around a description are trimmed without eating the first line indentation', function () {
    $markdown = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'x', frontmatter: ['alias' => 'x'], links: []),
        markerKey: 'ci',
        markerValue: 'x',
        carriedDescription: "\n\n  - nested opening\n  - and its sibling\n\n",
    );

    expect($markdown)->toContain("## Description\n\n  - nested opening\n  - and its sibling\n");
});

test('manual content keeps the indentation of its opening line too', function () {
    $markdown = (new MarkdownNoteRenderer)(
        noteContent: new NoteContent(alias: 'x', frontmatter: ['alias' => 'x'], links: []),
        markerKey: 'ci',
        markerValue: 'x',
        manualContent: "    first, indented\n    second, indented\n",
    );

    expect($markdown)->toEndWith("\n    first, indented\n    second, indented\n");
});
