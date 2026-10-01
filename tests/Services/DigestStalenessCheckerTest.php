<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Enums\CheckSeverity;
use WellDigit\Etruscan\Services\DigestStalenessChecker;
use WellDigit\Etruscan\Services\MapDigestRenderer;
use WellDigit\Etruscan\Services\VaultReader;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

beforeEach(function () {
    $this->vaultPath = sys_get_temp_dir().'/etruscan-stale-'.uniqid();
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/monitor.md', "---\nalias: monitor\ngenerated_by: etruscan\n---\n");
});

afterEach(function () {
    File::deleteDirectory($this->vaultPath);
});

test('an index older than its notes is reported', function () {
    File::put($this->vaultPath.'/map.md', "# Codebase map\n");
    touch($this->vaultPath.'/map.md', time() - 3600);

    $findings = app(DigestStalenessChecker::class)($this->vaultPath);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]->category)->toBe(CheckCategory::StaleDigest)
        ->and($findings[0]->severity)->toBe(CheckSeverity::Warning)
        ->and($findings[0]->message)->toContain('etruscan:export');
});

test('matching index content is clean regardless of timestamps', function () {
    $notes = app(VaultReader::class)($this->vaultPath, EtruscanConfig::markerKey());
    File::put($this->vaultPath.'/map.md', app(MapDigestRenderer::class)($notes, EtruscanConfig::exportAxis(), EtruscanConfig::markerKey()));
    touch($this->vaultPath.'/map.md', time() + 3600);

    expect(app(DigestStalenessChecker::class)($this->vaultPath))->toBe([]);
});

// Exporting is opt-in: a project that never exports is not a project with a
// problem, so the absence of an index must stay silent.
test('no index at all is not a finding', function () {
    expect(app(DigestStalenessChecker::class)($this->vaultPath))->toBe([]);
});

test('a missing vault is not a finding', function () {
    expect(app(DigestStalenessChecker::class)($this->vaultPath.'-missing'))->toBe([]);
});

// The index must not date itself against its own mtime.
test('an export left behind after all nodes are removed is stale', function () {
    File::deleteDirectory($this->vaultPath);
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/map.md', "# Codebase map\n");

    expect(app(DigestStalenessChecker::class)($this->vaultPath))->toHaveCount(1);
});

test('newer timestamps cannot hide stale exported content', function () {
    File::put($this->vaultPath.'/map.md', "# Incorrect map\n");
    touch($this->vaultPath.'/map.md', time() + 3600);
    expect(app(DigestStalenessChecker::class)($this->vaultPath))->toHaveCount(1);
});
