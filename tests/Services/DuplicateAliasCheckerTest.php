<?php

declare(strict_types=1);

use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Enums\CheckSeverity;
use WellDigit\Etruscan\Payloads\ScannedClass;
use WellDigit\Etruscan\Services\DuplicateAliasChecker;

test('an alias claimed by two classes in different namespaces is reported', function () {
    $findings = (new DuplicateAliasChecker)([
        new ScannedClass(fqcn: 'App\\Booking\\Status', axes: [], references: [], sourcePath: '/a.php', alias: 'status'),
        new ScannedClass(fqcn: 'App\\Invoice\\Status', axes: [], references: [], sourcePath: '/b.php', alias: 'status'),
        new ScannedClass(fqcn: 'App\\Unique', axes: [], references: [], sourcePath: '/c.php', alias: 'unique'),
    ]);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]->category)->toBe(CheckCategory::DuplicateAlias)
        ->and($findings[0]->severity)->toBe(CheckSeverity::Error)
        ->and($findings[0]->message)->toContain('status')
        ->and($findings[0]->message)->toContain('App\\Booking\\Status')
        ->and($findings[0]->message)->toContain('App\\Invoice\\Status');
});

test('non-node classes and unique aliases produce no findings', function () {
    $findings = (new DuplicateAliasChecker)([
        new ScannedClass(fqcn: 'App\\Foo', axes: [], references: [], sourcePath: '/a.php', alias: 'foo'),
        new ScannedClass(fqcn: 'App\\Plain', axes: [], references: [], sourcePath: '/b.php', alias: null),
    ]);

    expect($findings)->toBe([]);
});
