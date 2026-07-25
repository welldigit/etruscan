<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Payloads\NoteContent;
use WellDigit\Etruscan\Services\BrokenLinkChecker;

beforeEach(function () {
    $this->vaultPath = sys_get_temp_dir().'/etruscan-broken-'.uniqid();
});

afterEach(function () {
    File::deleteDirectory($this->vaultPath);
});

test('a wikilink to an unknown alias is reported with its file', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/note.md', "---\nalias: note\n---\n\nSee [[gone]] and [[known]].\n");

    $findings = (new BrokenLinkChecker)($this->vaultPath, [
        new NoteContent(alias: 'known', frontmatter: [], links: [], referencedBy: []),
        new NoteContent(alias: 'note', frontmatter: [], links: [], referencedBy: []),
    ]);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]->message)->toContain('gone')
        ->and($findings[0]->message)->toContain('note.md');
});

test('display and anchor wikilink forms resolve against the known aliases', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/note.md', "See [[known|Nice label]] and [[known#a-heading]].\n");

    $findings = (new BrokenLinkChecker)($this->vaultPath, [
        new NoteContent(alias: 'known', frontmatter: [], links: [], referencedBy: []),
    ]);

    expect($findings)->toBe([]);
});

test('a missing vault directory yields no findings', function () {
    expect((new BrokenLinkChecker)($this->vaultPath, []))->toBe([]);
});
