<?php

declare(strict_types=1);

use WellDigit\Etruscan\Mcp\Services\MapSearch;
use WellDigit\Etruscan\Payloads\ParsedNote;

/**
 * @param  array<string, string|list<string>>  $axes
 */
function searchNote(string $alias, string $class = '', string $description = '', array $axes = []): ParsedNote
{
    return new ParsedNote(
        alias: $alias,
        frontmatter: array_merge(['alias' => $alias, 'class' => $class, 'fqcn' => 'App\\'.$class], $axes),
        description: $description,
        manual: '',
        links: [],
        referencedBy: [],
    );
}

test('an exact alias match outranks class, axis and description matches', function () {
    $notesByAlias = [
        'booking' => searchNote(alias: 'booking', class: 'Booking'),
        'booking-create' => searchNote(alias: 'booking-create', class: 'BookingCreate'),
        'invoice' => searchNote(alias: 'invoice', class: 'Invoice', description: 'Charges the booking.'),
    ];

    $matches = (new MapSearch)($notesByAlias, 'booking');

    expect($matches[0]->alias)->toBe('booking')
        ->and(array_map(fn ($parsedNote) => $parsedNote->alias, $matches))->toContain('invoice');
});

test('axis values and description text are searchable', function () {
    $notesByAlias = [
        'a' => searchNote(alias: 'a', axes: ['domain' => 'billing']),
        'b' => searchNote(alias: 'b', description: 'Guards the billing cap.'),
        'c' => searchNote(alias: 'c'),
    ];

    $matches = (new MapSearch)($notesByAlias, 'billing');

    expect(array_map(fn ($parsedNote) => $parsedNote->alias, $matches))->toBe(['a', 'b']);
});

test('an unmatched query returns an empty list', function () {
    expect((new MapSearch)(['a' => searchNote(alias: 'a')], 'nonexistent-concept'))->toBe([]);
});

test('a blank query returns nothing rather than everything', function () {
    expect((new MapSearch)(['a' => searchNote(alias: 'a')], '   '))->toBe([]);
});

test('a spaced, hyphenated or namespaced query finds the kebab alias', function () {
    $notesByAlias = [
        'monitor' => searchNote(alias: 'monitor', class: 'Monitor'),
        'monitor-create' => searchNote(alias: 'monitor-create', class: 'MonitorCreate'),
    ];

    foreach (['monitor create', 'monitor-create', 'Monitor_Create'] as $query) {
        expect(array_map(fn ($parsedNote) => $parsedNote->alias, (new MapSearch)($notesByAlias, $query)))->toBe(['monitor-create']);
    }
});

test('every word must match somewhere on a note, not necessarily in one field', function () {
    $notesByAlias = [
        'invoice-send' => searchNote(alias: 'invoice-send', axes: ['domain' => 'billing']),
        'invoice-void' => searchNote(alias: 'invoice-void'),
    ];

    expect(array_map(fn ($parsedNote) => $parsedNote->alias, (new MapSearch)($notesByAlias, 'billing invoice')))->toBe(['invoice-send']);
});

test('the generation marker is not searchable as an axis', function () {
    $note = searchNote(alias: 'a', axes: ['generated_by' => 'etruscan']);

    expect((new MapSearch)(['a' => $note], 'etruscan'))->toBe([]);
});
