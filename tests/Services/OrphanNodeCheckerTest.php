<?php

declare(strict_types=1);

use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Payloads\NoteContent;
use WellDigit\Etruscan\Services\OrphanNodeChecker;

test('a node with no inbound and no outbound links is reported as isolated', function () {
    $findings = (new OrphanNodeChecker)([
        new NoteContent(alias: 'island', frontmatter: [], links: [], referencedBy: []),
        new NoteContent(alias: 'has-outbound', frontmatter: [], links: ['other'], referencedBy: []),
    ]);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]->category)->toBe(CheckCategory::OrphanNode)
        ->and($findings[0]->message)->toContain('island');
});

test('a node connected in either direction is not an orphan', function () {
    $findings = (new OrphanNodeChecker)([
        new NoteContent(alias: 'referenced-only', frontmatter: [], links: [], referencedBy: ['x']),
        new NoteContent(alias: 'referencing-only', frontmatter: [], links: ['y'], referencedBy: []),
    ]);

    expect($findings)->toBe([]);
});
