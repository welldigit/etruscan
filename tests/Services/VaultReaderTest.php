<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
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
