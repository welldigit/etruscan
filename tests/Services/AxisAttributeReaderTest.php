<?php

declare(strict_types=1);

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanDomain;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanSlice;
use WellDigit\Etruscan\Services\AxisAttributeReader;

test('the node attribute is matched by exact FQCN, with or without a leading backslash', function () {
    $axisAttributeReader = new AxisAttributeReader;

    expect($axisAttributeReader->isNodeAttribute(EtruscanNode::class))->toBeTrue()
        ->and($axisAttributeReader->isNodeAttribute('\\'.EtruscanNode::class))->toBeTrue()
        ->and($axisAttributeReader->isNodeAttribute(EtruscanDomain::class))->toBeFalse()
        ->and($axisAttributeReader->isNodeAttribute('Some\\Other\\EtruscanNode'))->toBeFalse();
});

test('every EtruscanAxis subclass counts as an axis attribute', function () {
    $axisAttributeReader = new AxisAttributeReader;

    expect($axisAttributeReader->isAxisAttribute(EtruscanDomain::class))->toBeTrue()
        ->and($axisAttributeReader->isAxisAttribute(EtruscanLayer::class))->toBeTrue()
        ->and($axisAttributeReader->isAxisAttribute(EtruscanContext::class))->toBeTrue()
        ->and($axisAttributeReader->isAxisAttribute(EtruscanSlice::class))->toBeTrue()
        ->and($axisAttributeReader->isAxisAttribute('\\'.EtruscanDomain::class))->toBeTrue();
});

test('the node attribute and unrelated classes are not axis attributes', function () {
    $axisAttributeReader = new AxisAttributeReader;

    expect($axisAttributeReader->isAxisAttribute(EtruscanNode::class))->toBeFalse()
        ->and($axisAttributeReader->isAxisAttribute(Attribute::class))->toBeFalse()
        ->and($axisAttributeReader->isAxisAttribute('Vendor\\Does\\Not\\Exist'))->toBeFalse();
});

test('the axis key strips a leading Etruscan segment and lowercases the short name', function () {
    $axisAttributeReader = new AxisAttributeReader;

    expect($axisAttributeReader->resolveAxisKey(EtruscanDomain::class))->toBe('domain')
        ->and($axisAttributeReader->resolveAxisKey(EtruscanLayer::class))->toBe('layer')
        ->and($axisAttributeReader->resolveAxisKey(EtruscanContext::class))->toBe('context')
        ->and($axisAttributeReader->resolveAxisKey('My\\Vocabulary\\EtruscanTeamArea'))->toBe('teamarea');
});

test('a short name where Etruscan is not a distinct PascalCase segment keeps its full name', function () {
    $axisAttributeReader = new AxisAttributeReader;

    expect($axisAttributeReader->resolveAxisKey('My\\Vocabulary\\Etruscania'))->toBe('etruscania')
        ->and($axisAttributeReader->resolveAxisKey('My\\Vocabulary\\Estimate'))->toBe('estimate');
});
