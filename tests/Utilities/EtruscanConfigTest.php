<?php

declare(strict_types=1);

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
