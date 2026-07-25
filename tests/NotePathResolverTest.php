<?php

declare(strict_types=1);

use WellDigit\Etruscan\Exceptions\InvalidGroupingValueException;
use WellDigit\Etruscan\Payloads\NoteContent;
use WellDigit\Etruscan\Services\NotePathResolver;

test('a null grouping axis keeps the vault flat', function () {
    $notePathResolver = new NotePathResolver(groupByAxisKeys: []);

    $path = $notePathResolver(new NoteContent(
        alias: 'booking-create',
        frontmatter: ['alias' => 'booking-create', 'domain' => 'booking'],
        links: [],
    ));

    expect($path)->toBe('booking-create.md');
});

test('a scalar axis value groups the note into a subfolder', function () {
    $notePathResolver = new NotePathResolver(groupByAxisKeys: ['domain']);

    $path = $notePathResolver(new NoteContent(
        alias: 'booking-create',
        frontmatter: ['alias' => 'booking-create', 'domain' => 'booking'],
        links: [],
    ));

    expect($path)->toBe('booking/booking-create.md');
});

test('a note lacking the grouping axis stays at the vault root', function (string $groupByAxisKey) {
    $notePathResolver = new NotePathResolver(groupByAxisKeys: [$groupByAxisKey]);

    $path = $notePathResolver(new NoteContent(
        alias: 'booking-create',
        frontmatter: ['alias' => 'booking-create', 'layer' => 'action'],
        links: [],
    ));

    expect($path)->toBe('booking-create.md');
})->with(['domain', 'never-seen-axis']);

test('a multi-value axis groups under the first value in sorted order', function () {
    $notePathResolver = new NotePathResolver(groupByAxisKeys: ['context']);

    $path = $notePathResolver(new NoteContent(
        alias: 'monitor-query',
        frontmatter: ['alias' => 'monitor-query', 'context' => ['monitor', 'incident']],
        links: [],
    ));

    expect($path)->toBe('incident/monitor-query.md');
});

test('axis values are sanitized into safe directory names', function (string $rawValue, string $expectedDirectory) {
    $notePathResolver = new NotePathResolver(groupByAxisKeys: ['domain']);

    $path = $notePathResolver(new NoteContent(
        alias: 'booking-create',
        frontmatter: ['alias' => 'booking-create', 'domain' => $rawValue],
        links: [],
    ));

    expect($path)->toBe($expectedDirectory.'/booking-create.md');
})->with([
    'spaces and case' => ['Team Ops', 'team-ops'],
    'path traversal' => ['../evil', 'evil'],
    'mixed case' => ['MiXeD', 'mixed'],
    'already a slug' => ['status_page-v2', 'status_page-v2'],
]);

test('multiple grouping axes nest folders in the configured order', function () {
    $notePathResolver = new NotePathResolver(groupByAxisKeys: ['layer', 'domain']);

    $path = $notePathResolver(new NoteContent(
        alias: 'booking-create',
        frontmatter: ['alias' => 'booking-create', 'domain' => 'booking', 'layer' => 'action'],
        links: [],
    ));

    expect($path)->toBe('action/booking/booking-create.md');
});

test('a nesting level whose axis the note does not carry is skipped', function () {
    $notePathResolver = new NotePathResolver(groupByAxisKeys: ['layer', 'domain']);

    $path = $notePathResolver(new NoteContent(
        alias: 'booking-create',
        frontmatter: ['alias' => 'booking-create', 'domain' => 'booking'],
        links: [],
    ));

    expect($path)->toBe('booking/booking-create.md');
});

test('a note carrying none of the grouping axes stays at the vault root', function () {
    $notePathResolver = new NotePathResolver(groupByAxisKeys: ['layer', 'domain']);

    $path = $notePathResolver(new NoteContent(
        alias: 'booking-create',
        frontmatter: ['alias' => 'booking-create'],
        links: [],
    ));

    expect($path)->toBe('booking-create.md');
});

test('a value that cannot become a directory name fails loud', function () {
    $notePathResolver = new NotePathResolver(groupByAxisKeys: ['domain']);

    expect(fn () => $notePathResolver(new NoteContent(
        alias: 'booking-create',
        frontmatter: ['alias' => 'booking-create', 'domain' => '///'],
        links: [],
    )))->toThrow(InvalidGroupingValueException::class, 'booking-create');
});
