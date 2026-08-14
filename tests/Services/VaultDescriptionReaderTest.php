<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Services\VaultDescriptionReader;

beforeEach(function () {
    $this->vaultPath = sys_get_temp_dir().'/etruscan-descriptions-'.uniqid();
});

afterEach(function () {
    File::deleteDirectory($this->vaultPath);
});

test('descriptions come back keyed by alias', function () {
    File::ensureDirectoryExists($this->vaultPath.'/scan');
    File::put($this->vaultPath.'/zulu.md', "---\nalias: zulu\ngenerated_by: etruscan\n---\n\n## Description\n\nLast.\n");
    File::put($this->vaultPath.'/scan/alpha.md', "---\nalias: alpha\ngenerated_by: etruscan\n---\n\n## Description\n\nFirst.\n");

    expect(app(VaultDescriptionReader::class)($this->vaultPath, 'generated_by'))
        ->toBe(['alpha' => 'First.', 'zulu' => 'Last.']);
});

test('a note whose description is still an empty slot is left out', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/empty.md', "---\nalias: empty\ngenerated_by: etruscan\n---\n\n## Description\n\n## References\n\n- [[other]]\n");

    expect(app(VaultDescriptionReader::class)($this->vaultPath, 'generated_by'))->toBe([]);
});

test('a missing vault yields no descriptions', function () {
    expect(app(VaultDescriptionReader::class)($this->vaultPath, 'generated_by'))->toBe([]);
});
