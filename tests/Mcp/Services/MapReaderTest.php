<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Mcp\Services\MapReader;

beforeEach(function () {
    $this->vaultPath = sys_get_temp_dir().'/etruscan-map-reader-'.uniqid();
    config()->set('etruscan.vault_path', $this->vaultPath);
});

afterEach(function () {
    File::deleteDirectory($this->vaultPath);
});

test('the configured vault is read, keyed by alias', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/monitor.md', "---\nalias: monitor\ngenerated_by: etruscan\n---\n\n## Description\n\nWatches.\n");

    $notesByAlias = app(MapReader::class)();

    expect(array_keys($notesByAlias))->toBe(['monitor'])
        ->and($notesByAlias['monitor']->description)->toBe('Watches.');
});

test('a missing vault reads as an empty map rather than an error', function () {
    expect(app(MapReader::class)())->toBe([]);
});

test('the configured marker decides what counts as part of the map', function () {
    config()->set('etruscan.generated_marker', 'made_by');

    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/ours.md', "---\nalias: ours\nmade_by: etruscan\n---\n");
    File::put($this->vaultPath.'/theirs.md', "---\nalias: theirs\ngenerated_by: etruscan\n---\n");

    expect(array_keys(app(MapReader::class)()))->toBe(['ours']);
});

test('the empty-map message names the command that fixes it', function () {
    expect(MapReader::EMPTY_MAP_MESSAGE)->toContain('etruscan:generate');
});
