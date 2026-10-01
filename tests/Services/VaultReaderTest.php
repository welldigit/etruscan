<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use WellDigit\Etruscan\Services\VaultReader;

beforeEach(function () {
    $this->vaultPath = sys_get_temp_dir().'/etruscan-reader-'.uniqid();
});

afterEach(function () {
    File::deleteDirectory($this->vaultPath);
});

test('generated notes are indexed by alias, sorted, across subfolders', function () {
    File::ensureDirectoryExists($this->vaultPath.'/scan');
    File::put($this->vaultPath.'/zulu.md', "---\nalias: zulu\ngenerated_by: etruscan\n---\n\n## Description\n\nLast.\n");
    File::put($this->vaultPath.'/scan/alpha.md', "---\nalias: alpha\ngenerated_by: etruscan\n---\n\n## Description\n\nFirst.\n");

    $notesByAlias = app(VaultReader::class)($this->vaultPath, 'generated_by');

    expect(array_keys($notesByAlias))->toBe(['alpha', 'zulu'])
        ->and($notesByAlias['alpha']->description)->toBe('First.');
});

test('hand-written notes without the marker are not part of the map', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/manual.md', "# Notes\n\nMine.\n");
    File::put($this->vaultPath.'/generated.md', "---\nalias: generated\ngenerated_by: etruscan\n---\n");

    $notesByAlias = app(VaultReader::class)($this->vaultPath, 'generated_by');

    expect(array_keys($notesByAlias))->toBe(['generated']);
});

test('a missing vault directory yields an empty map', function () {
    expect(app(VaultReader::class)($this->vaultPath, 'generated_by'))->toBe([]);
});

// The path is what lookup-node prints when it clips a long human-notes
// section, so it has to be openable: app-relative inside the project, and
// left absolute for a vault configured somewhere else entirely.
test('a note carries its path, relative to the app root when it lives under it', function () {
    $insideBase = base_path('etruscan-reader-inside');
    File::ensureDirectoryExists($insideBase);
    File::put($insideBase.'/alpha.md', "---\nalias: alpha\ngenerated_by: etruscan\n---\n");

    $notesByAlias = app(VaultReader::class)($insideBase, 'generated_by');

    expect($notesByAlias['alpha']->path)->toBe('etruscan-reader-inside'.DIRECTORY_SEPARATOR.'alpha.md');

    File::deleteDirectory($insideBase);
});

test('a vault outside the app root keeps its absolute path', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/alpha.md', "---\nalias: alpha\ngenerated_by: etruscan\n---\n");

    $notesByAlias = app(VaultReader::class)($this->vaultPath, 'generated_by');

    expect($notesByAlias['alpha']->path)->toBe($this->vaultPath.DIRECTORY_SEPARATOR.'alpha.md');
});

// A note is served straight into an MCP response, where one invalid byte makes
// json_encode() fail and laravel/mcp emit a zero-length frame: the tool call
// returns nothing and raises nothing. Scrubbed on the way in, and logged.
test('a note holding invalid bytes is scrubbed rather than poisoning the answer', function () {
    $logger = Log::spy();

    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/broken.md', "---\nalias: broken\ngenerated_by: etruscan\n---\n\n## Description\n\nCaf\xE9 cache guard.\n");

    $notesByAlias = app(VaultReader::class)($this->vaultPath, 'generated_by');
    $description = $notesByAlias['broken']->description;

    expect(mb_check_encoding($description, 'UTF-8'))->toBeTrue()
        ->and(json_encode(['text' => $description], JSON_UNESCAPED_UNICODE))->not->toBeFalse()
        ->and($description)->toContain('cache guard');

    $logger->shouldHaveReceived('warning');
});

test('a valid note is passed through untouched, em dashes included', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/fine.md', "---\nalias: fine\ngenerated_by: etruscan\n---\n\n## Description\n\nGuards the plan cap — before persistence.\n");

    $notesByAlias = app(VaultReader::class)($this->vaultPath, 'generated_by');

    expect($notesByAlias['fine']->description)->toBe('Guards the plan cap — before persistence.');
});
