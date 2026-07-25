<?php

declare(strict_types=1);

use WellDigit\Etruscan\Utilities\ScannedFolderResolver;

test('an array of relative folders resolves each against the application root', function () {
    expect(ScannedFolderResolver::resolve(['app', 'src']))->toBe([base_path('app'), base_path('src')]);
});

test('a comma-separated string is split, trimmed and resolved like an array', function () {
    expect(ScannedFolderResolver::resolve(' app , src '))->toBe([base_path('app'), base_path('src')]);
});

test('an absolute folder in the list is left untouched while relative ones still resolve', function () {
    expect(ScannedFolderResolver::resolve(['/opt/vendor/pkg/src', 'app']))->toBe(['/opt/vendor/pkg/src', base_path('app')]);
});

test('empty segments and duplicates are dropped', function () {
    expect(ScannedFolderResolver::resolve('app,,app, src'))->toBe([base_path('app'), base_path('src')]);
});

test('non-string array entries are ignored', function () {
    expect(ScannedFolderResolver::resolve(['app', 42, null, 'src']))->toBe([base_path('app'), base_path('src')]);
});

test('an unsupported input type resolves to an empty list', function (mixed $unsupported) {
    expect(ScannedFolderResolver::resolve($unsupported))->toBe([]);
})->with([
    'null' => [null],
    'int' => [42],
    'bool' => [true],
]);
