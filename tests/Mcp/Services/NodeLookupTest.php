<?php

declare(strict_types=1);

use WellDigit\Etruscan\Mcp\Services\NodeLookup;
use WellDigit\Etruscan\Payloads\ParsedNote;

function mapNote(string $alias): ParsedNote
{
    return new ParsedNote(alias: $alias, frontmatter: ['alias' => $alias], description: '', manual: '', links: [], referencedBy: []);
}

test('a known alias returns its note with no suggestions', function () {
    $lookup = (new NodeLookup)(['monitor-create' => mapNote('monitor-create')], 'monitor-create');

    expect($lookup['note'])->not->toBeNull()
        ->and($lookup['note']->alias)->toBe('monitor-create')
        ->and($lookup['suggestions'])->toBe([]);
});

test('an unknown alias returns null with the closest aliases suggested', function () {
    $notesByAlias = [
        'monitor-create' => mapNote('monitor-create'),
        'monitor-update' => mapNote('monitor-update'),
        'invoice-send' => mapNote('invoice-send'),
    ];

    $lookup = (new NodeLookup)($notesByAlias, 'monitor-creat');

    expect($lookup['note'])->toBeNull()
        ->and($lookup['suggestions'])->toContain('monitor-create')
        ->and($lookup['suggestions'])->not->toContain('invoice-send');
});

test('a substring of an alias also surfaces it as a suggestion', function () {
    $lookup = (new NodeLookup)(['usage-report-builder' => mapNote('usage-report-builder')], 'report');

    expect($lookup['note'])->toBeNull()
        ->and($lookup['suggestions'])->toBe(['usage-report-builder']);
});
