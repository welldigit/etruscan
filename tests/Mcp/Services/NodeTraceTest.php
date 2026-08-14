<?php

declare(strict_types=1);

use WellDigit\Etruscan\Enums\TraceDirection;
use WellDigit\Etruscan\Mcp\Services\NodeTrace;
use WellDigit\Etruscan\Payloads\ParsedNote;

/**
 * @param  list<string>  $links
 * @param  list<string>  $referencedBy
 */
function traceNote(string $alias, array $links = [], array $referencedBy = [], string $description = ''): ParsedNote
{
    return new ParsedNote(
        alias: $alias,
        frontmatter: ['alias' => $alias],
        description: $description,
        manual: '',
        links: $links,
        referencedBy: $referencedBy,
    );
}

test('both directions come back with neighbour descriptions', function () {
    $notesByAlias = [
        'hub' => traceNote(alias: 'hub', links: ['leaf'], referencedBy: ['root']),
        'leaf' => traceNote(alias: 'leaf', description: "A leaf\nnode."),
        'root' => traceNote(alias: 'root', description: 'The root.'),
    ];

    $trace = (new NodeTrace)($notesByAlias, 'hub', TraceDirection::Both);

    expect($trace)->not->toBeNull()
        ->and($trace['references'] ?? null)->toBe(['leaf' => 'A leaf node.'])
        ->and($trace['referencedBy'] ?? null)->toBe(['root' => 'The root.']);
});

test('direction restricts the trace to one side', function () {
    $notesByAlias = [
        'hub' => traceNote(alias: 'hub', links: ['leaf'], referencedBy: ['root']),
        'leaf' => traceNote(alias: 'leaf'),
        'root' => traceNote(alias: 'root'),
    ];

    expect((new NodeTrace)($notesByAlias, 'hub', TraceDirection::Out)['referencedBy'] ?? null)->toBe([])
        ->and((new NodeTrace)($notesByAlias, 'hub', TraceDirection::In)['references'] ?? null)->toBe([]);
});

test('an unknown alias yields null', function () {
    expect((new NodeTrace)([], 'ghost', TraceDirection::Both))->toBeNull();
});
