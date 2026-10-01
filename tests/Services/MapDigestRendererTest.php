<?php

declare(strict_types=1);

use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Services\MapDigestRenderer;
use WellDigit\Etruscan\Utilities\NodeSummaryLine;

/**
 * @param  string|list<string>  $context
 */
function digestNote(string $alias, string $description, string|array $context = 'monitor'): ParsedNote
{
    return new ParsedNote(
        alias: $alias,
        frontmatter: ['alias' => $alias, 'source' => 'src/'.$alias.'.php', 'context' => $context, 'generated_by' => 'etruscan'],
        description: $description,
        manual: '',
        links: [],
        referencedBy: [],
    );
}

test('the index groups nodes by axis, one line each', function () {
    $digest = app(MapDigestRenderer::class)([
        'monitor' => digestNote('monitor', 'The uptime monitor aggregate.'),
        'invoice' => digestNote('invoice', 'A billable invoice.', 'billing'),
    ], 'context', 'generated_by');

    expect($digest)->toContain('2 nodes, grouped by [context]')
        ->and($digest)->toContain('## billing')
        ->and($digest)->toContain('## monitor')
        ->and($digest)->toContain('- monitor (src/monitor.php) — The uptime monitor aggregate.')
        ->and($digest)->toEndWith("\n");
});

// The index is imported into CLAUDE.md, so it should say what it is and what it
// is not — an agent reading it has no other context for where it came from.
test('the header frames the index as a pointer at the code, not a stand-in for it', function () {
    $digest = app(MapDigestRenderer::class)(['monitor' => digestNote('monitor', 'Intent.')], 'context', 'generated_by');

    expect($digest)->toContain('# Codebase map')
        ->and($digest)->toContain('not a substitute for it')
        ->and($digest)->toContain('the source is')
        ->and($digest)->toContain('etruscan:export');
});

/**
 * Re-exporting an unchanged map must produce the same bytes: the index is
 * committed and imported into a cached prefix, so churn dirties every diff and
 * invalidates the cache the artifact exists to fill.
 */
test('the same map renders byte-identically every time', function () {
    $notes = [
        'monitor' => digestNote('monitor', 'The uptime monitor aggregate.'),
        'invoice' => digestNote('invoice', 'A billable invoice.', 'billing'),
    ];

    expect(app(MapDigestRenderer::class)($notes, 'context', 'generated_by'))
        ->toBe(app(MapDigestRenderer::class)($notes, 'context', 'generated_by'));
});

test('a node serving several contexts is listed under each of them', function () {
    $digest = app(MapDigestRenderer::class)([
        'shared' => digestNote('shared', 'Serves two contexts.', ['monitor', 'billing']),
    ], 'context', 'generated_by');

    expect(substr_count($digest, '- shared (src/shared.php)'))->toBe(2);
});

test('descriptions are clipped so one verbose node cannot dominate the index', function () {
    $digest = app(MapDigestRenderer::class)([
        'verbose' => digestNote('verbose', str_repeat('a', 600)),
    ], 'context', 'generated_by');

    $line = collect(explode("\n", $digest))->first(fn (string $l): bool => str_starts_with($l, '- verbose'));

    expect($line)->toEndWith('...')
        ->and(mb_strlen((string) $line))->toBeLessThan(NodeSummaryLine::DESCRIPTION_LIMIT + 100);
});

test('an axis no node carries falls back the way map-overview does', function () {
    $digest = app(MapDigestRenderer::class)(['monitor' => digestNote('monitor', 'Intent.')], 'domain', 'generated_by');

    expect($digest)->toContain('grouped by [context]');
});
