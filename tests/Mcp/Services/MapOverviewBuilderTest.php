<?php

declare(strict_types=1);

use WellDigit\Etruscan\Mcp\Services\MapOverviewBuilder;
use WellDigit\Etruscan\Payloads\ParsedNote;

function overviewNote(string $alias, array $frontmatter = []): ParsedNote
{
    return new ParsedNote(
        alias: $alias,
        frontmatter: array_merge(['alias' => $alias, 'generated_by' => 'etruscan'], $frontmatter),
        description: '',
        manual: '',
        links: [],
        referencedBy: [],
    );
}

test('nodes group by the requested axis, multi-value axes land in every group', function () {
    $overview = (new MapOverviewBuilder)([
        'a' => overviewNote(alias: 'a', frontmatter: ['context' => 'scan']),
        'b' => overviewNote(alias: 'b', frontmatter: ['context' => ['scan', 'vault']]),
        'c' => overviewNote(alias: 'c'),
    ], 'context', 'generated_by');

    expect($overview['axis'])->toBe('context')
        ->and($overview['groups']['scan'])->toBe(['a', 'b'])
        ->and($overview['groups']['vault'])->toBe(['b'])
        ->and($overview['groups']['(none)'])->toBe(['c']);
});

test('an absent axis falls back to the first real axis, never the marker or identity keys', function () {
    $overview = (new MapOverviewBuilder)([
        'a' => overviewNote(alias: 'a', frontmatter: ['layer' => 'service']),
    ], 'nonexistent', 'generated_by');

    expect($overview['axis'])->toBe('layer')
        ->and($overview['groups'])->toBe(['service' => ['a']]);
});
