<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

test('the vault path is resolved absolute against the app base path', function () {
    config()->set('etruscan.vault_path', '.etruscan');

    expect(EtruscanConfig::vaultPath())->toBe(base_path('.etruscan'));
});

test('an already-absolute vault path is left alone', function () {
    config()->set('etruscan.vault_path', '/srv/maps/etruscan');

    expect(EtruscanConfig::vaultPath())->toBe('/srv/maps/etruscan');
});

test('an override wins over the configured vault, and is resolved the same way', function () {
    config()->set('etruscan.vault_path', '.etruscan');

    expect(EtruscanConfig::vaultPath('somewhere-else'))->toBe(base_path('somewhere-else'))
        ->and(EtruscanConfig::vaultPath('/tmp/vault'))->toBe('/tmp/vault');
});

test('an empty or absent override falls back to the configured vault', function () {
    config()->set('etruscan.vault_path', '.etruscan');

    expect(EtruscanConfig::vaultPath(''))->toBe(base_path('.etruscan'))
        ->and(EtruscanConfig::vaultPath(null))->toBe(base_path('.etruscan'));
});

test('a blank or non-string config value falls back to the documented default', function () {
    config()->set('etruscan.vault_path', '');
    config()->set('etruscan.generated_marker', null);
    config()->set('etruscan.generated_value', ['not', 'a', 'string']);

    expect(EtruscanConfig::vaultPath())->toBe(base_path('.etruscan'))
        ->and(EtruscanConfig::markerKey())->toBe('generated_by')
        ->and(EtruscanConfig::markerValue())->toBe('etruscan');
});

test('group_by accepts a string, an array, or nothing at all', function () {
    config()->set('etruscan.group_by', 'context');
    expect(EtruscanConfig::groupBy())->toBe('context');

    config()->set('etruscan.group_by', ['layer', 'domain']);
    expect(EtruscanConfig::groupBy())->toBe('layer,domain');

    config()->set('etruscan.group_by', null);
    expect(EtruscanConfig::groupBy())->toBeNull();
});

test('usage tracking reads as a boolean and can be switched off', function () {
    config()->set('etruscan.usage_tracking', false);
    expect(EtruscanConfig::usageTracking())->toBeFalse();

    config()->set('etruscan.usage_tracking', '0');
    expect(EtruscanConfig::usageTracking())->toBeFalse();

    config()->set('etruscan.usage_tracking', true);
    expect(EtruscanConfig::usageTracking())->toBeTrue();
});

test('an unconfigured or null usage_tracking stays on — silence is not a switch-off', function () {
    config()->set('etruscan.usage_tracking', null);
    expect(EtruscanConfig::usageTracking())->toBeTrue();

    config()->offsetUnset('etruscan.usage_tracking');
    expect(EtruscanConfig::usageTracking())->toBeTrue();
});

test('unset scanned folders scan the conventional roots that exist, and only those', function () {
    $basePath = sys_get_temp_dir().'/etruscan-roots-'.uniqid();
    File::ensureDirectoryExists($basePath.'/app');
    $originalBasePath = base_path();
    app()->setBasePath($basePath);
    config()->set('etruscan.scanned_folders', null);

    try {
        expect(EtruscanConfig::scannedFolders())->toBe([$basePath.'/app']);

        File::ensureDirectoryExists($basePath.'/src');
        expect(EtruscanConfig::scannedFolders())->toBe([$basePath.'/app', $basePath.'/src']);
    } finally {
        app()->setBasePath($originalBasePath);
        File::deleteDirectory($basePath);
    }
});

test('with no conventional root present, app is kept so the gap is still reported', function () {
    $basePath = sys_get_temp_dir().'/etruscan-roots-'.uniqid();
    File::ensureDirectoryExists($basePath);
    $originalBasePath = base_path();
    app()->setBasePath($basePath);
    config()->set('etruscan.scanned_folders', null);

    try {
        expect(EtruscanConfig::scannedFolders())->toBe([$basePath.'/app']);
    } finally {
        app()->setBasePath($originalBasePath);
        File::deleteDirectory($basePath);
    }
});

test('an explicit scanned-folder list is strict: missing entries are kept', function () {
    config()->set('etruscan.scanned_folders', ['app', 'definitely-missing']);

    expect(EtruscanConfig::scannedFolders())->toBe([base_path('app'), base_path('definitely-missing')]);
});

test('a blank scanned-folders env reads as unset, never as "scan nothing"', function () {
    config()->set('etruscan.scanned_folders', '  ');

    expect(EtruscanConfig::scannedFolders())->not->toBe([])
        ->and(EtruscanConfig::scannedFolders())->toContain(base_path('app'));
});
