<?php

declare(strict_types=1);

use WellDigit\Etruscan\Utilities\AbsolutePathResolver;

test('a relative path is resolved against the application root', function () {
    expect(AbsolutePathResolver::resolve('vault'))->toBe(base_path('vault'));
});

test('a nested relative path is resolved against the application root', function () {
    expect(AbsolutePathResolver::resolve('packages/acme/src'))->toBe(base_path('packages/acme/src'));
});

test('an absolute path is left untouched', function (string $absolutePath) {
    expect(AbsolutePathResolver::resolve($absolutePath))->toBe($absolutePath);
})->with([
    'unix' => ['/var/www/vault'],
    'windows drive letter' => ['C:\\Projects\\vault'],
    'windows drive letter with forward slash' => ['C:/Projects/vault'],
    'unc-style leading backslash' => ['\\\\server\\share\\vault'],
]);
