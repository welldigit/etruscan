<?php

declare(strict_types=1);

use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Utilities\NodeSummaryLine;

function summaryNote(string $description, ?string $source = 'src/Monitor.php'): ParsedNote
{
    return new ParsedNote(
        alias: 'monitor',
        frontmatter: $source === null ? ['alias' => 'monitor'] : ['alias' => 'monitor', 'source' => $source],
        description: $description,
        manual: '',
        links: [],
        referencedBy: [],
    );
}

test('a node renders as alias, source and intent', function () {
    expect(NodeSummaryLine::render(summaryNote('The uptime monitor aggregate.')))
        ->toBe('- monitor (src/Monitor.php) — The uptime monitor aggregate.');
});

test('a multi-line description is collapsed onto one line', function () {
    expect(NodeSummaryLine::render(summaryNote("Guards the plan cap\nbefore persistence.")))
        ->toBe('- monitor (src/Monitor.php) — Guards the plan cap before persistence.');
});

test('a long description is clipped to the shared limit', function () {
    $line = NodeSummaryLine::render(summaryNote(str_repeat('a', 400)));

    expect($line)->toEndWith('...')
        ->and(mb_strlen($line))->toBeLessThanOrEqual(NodeSummaryLine::DESCRIPTION_LIMIT + mb_strlen('- monitor (src/Monitor.php) — '));
});

test('the parts that are missing are simply absent', function () {
    expect(NodeSummaryLine::render(summaryNote('', null)))->toBe('- monitor')
        ->and(NodeSummaryLine::render(summaryNote('Intent.', null)))->toBe('- monitor — Intent.');
});
