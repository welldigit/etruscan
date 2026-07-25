<?php

declare(strict_types=1);

use WellDigit\Etruscan\Enums\CheckSeverity;
use WellDigit\Etruscan\Payloads\ScannedClass;
use WellDigit\Etruscan\Services\VocabularyChecker;

test('a value outside a configured vocabulary is an error, with the closest match suggested', function () {
    $findings = (new VocabularyChecker)(
        [new ScannedClass(fqcn: 'App\\X', axes: ['layer' => ['serivce']], references: [], sourcePath: '/x.php', alias: 'x')],
        ['layer' => ['action', 'service', 'model']],
    );

    expect($findings)->toHaveCount(1)
        ->and($findings[0]->severity)->toBe(CheckSeverity::Error)
        ->and($findings[0]->message)->toContain('serivce')
        ->and($findings[0]->message)->toContain('did you mean [service]');
});

test('a configured value that is allowed produces no finding', function () {
    $findings = (new VocabularyChecker)(
        [new ScannedClass(fqcn: 'App\\X', axes: ['layer' => ['service']], references: [], sourcePath: '/x.php', alias: 'x')],
        ['layer' => ['action', 'service']],
    );

    expect($findings)->toBe([]);
});

test('a free-form axis warns on near-identical values that look like a typo', function () {
    $findings = (new VocabularyChecker)([
        new ScannedClass(fqcn: 'App\\A', axes: ['layer' => ['service']], references: [], sourcePath: '/a.php', alias: 'a'),
        new ScannedClass(fqcn: 'App\\B', axes: ['layer' => ['serivce']], references: [], sourcePath: '/b.php', alias: 'b'),
    ], []);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]->severity)->toBe(CheckSeverity::Warning)
        ->and($findings[0]->message)->toContain('service')
        ->and($findings[0]->message)->toContain('serivce');
});

test('genuinely distinct free-form values do not warn', function () {
    $findings = (new VocabularyChecker)([
        new ScannedClass(fqcn: 'App\\A', axes: ['domain' => ['booking']], references: [], sourcePath: '/a.php', alias: 'a'),
        new ScannedClass(fqcn: 'App\\B', axes: ['domain' => ['invoice']], references: [], sourcePath: '/b.php', alias: 'b'),
    ], []);

    expect($findings)->toBe([]);
});
