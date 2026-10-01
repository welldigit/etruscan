<?php

declare(strict_types=1);

use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Enums\CheckSeverity;
use WellDigit\Etruscan\Payloads\ReferenceEvidence;
use WellDigit\Etruscan\Payloads\ScannedClass;
use WellDigit\Etruscan\Services\AxisAttributeReader;
use WellDigit\Etruscan\Services\UnresolvedAttributeChecker;

test('an Etruscan attribute resolving to no class is reported with its file and line', function () {
    $findings = (new UnresolvedAttributeChecker(new AxisAttributeReader))([
        new ScannedClass(
            fqcn: 'App\\Actions\\BookingCreate',
            axes: [],
            references: [],
            sourcePath: '/app/Actions/BookingCreate.php',
            evidence: [new ReferenceEvidence('App\\Actions\\EtruscanNode', 'attribute', 12)],
        ),
    ]);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]->category)->toBe(CheckCategory::UnresolvedAttribute)
        ->and($findings[0]->severity)->toBe(CheckSeverity::Warning)
        ->and($findings[0]->message)->toContain('#[EtruscanNode]')
        ->and($findings[0]->message)->toContain('App\\Actions\\BookingCreate')
        ->and($findings[0]->message)->toContain('/app/Actions/BookingCreate.php:12')
        ->and($findings[0]->message)->toContain('App\\Actions\\EtruscanNode')
        ->and($findings[0]->message)->toContain('#[\\EtruscanNode]');
});

test('attributes that resolve, and usages that are not attributes, are left alone', function () {
    $findings = (new UnresolvedAttributeChecker(new AxisAttributeReader))([
        new ScannedClass(
            fqcn: 'App\\Actions\\BookingCreate',
            axes: ['layer' => ['action']],
            references: [],
            sourcePath: '/app/Actions/BookingCreate.php',
            alias: 'booking-create',
            evidence: [
                new ReferenceEvidence(EtruscanNode::class, 'attribute', 9),
                new ReferenceEvidence(EtruscanLayer::class, 'attribute', 10),
                new ReferenceEvidence(WellDigit\Etruscan\Attributes\EtruscanNode::class, 'attribute', 11),
                // A missing class that nothing about Etruscan claims: someone else's problem.
                new ReferenceEvidence('App\\Actions\\Gone', 'type', 20),
                new ReferenceEvidence('App\\Actions\\SomeAttribute', 'attribute', 21),
            ],
        ),
    ]);

    expect($findings)->toBe([]);
});

test('the same unresolved attribute on one class is reported once', function () {
    $findings = (new UnresolvedAttributeChecker(new AxisAttributeReader))([
        new ScannedClass(
            fqcn: 'App\\Actions\\BookingCreate',
            axes: [],
            references: [],
            sourcePath: '/app/Actions/BookingCreate.php',
            evidence: [
                new ReferenceEvidence('App\\Actions\\EtruscanContext', 'attribute', 10),
                new ReferenceEvidence('App\\Actions\\EtruscanContext', 'attribute', 11),
                new ReferenceEvidence('App\\Actions\\EtruscanLayer', 'attribute', 12),
            ],
        ),
    ]);

    expect($findings)->toHaveCount(2)
        ->and($findings[0]->message)->toContain('#[EtruscanContext]')
        ->and($findings[1]->message)->toContain('#[EtruscanLayer]');
});
