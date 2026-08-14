<?php

declare(strict_types=1);

use WellDigit\Etruscan\Payloads\UsageReport;
use WellDigit\Etruscan\Services\UsagePageRenderer;

function usageReport(int $events = 3): UsageReport
{
    return new UsageReport(
        events: $events,
        byType: ['lookup' => 2, 'search' => 1],
        byDay: ['2026-07-27' => ['events' => 3, 'misses' => 1]],
        hits: 2,
        misses: 1,
        topNodes: ['vault-writer' => 2],
        missedSubjects: ['plan-cap' => 1],
        emptySearches: [],
        charsServed: 840,
        distinctNodesConsulted: 1,
        malformedLines: 0,
        newerSchemaLines: 0,
        windowDays: null,
    );
}

test('the dashboard embeds the report as JSON with the expected keys', function () {
    $html = (new UsagePageRenderer)(usageReport());

    expect($html)->toContain('"events":3')
        ->and($html)->toContain('"top_nodes":{"vault-writer":2}')
        ->and($html)->toContain('"missed_subjects":{"plan-cap":1}')
        ->and($html)->toContain('"by_day":{"2026-07-27":{"events":3,"misses":1}}')
        ->and($html)->toContain('"chars_served":840')
        ->and($html)->toContain('Etruscan');
});

test('script-breaking characters in subjects are escaped', function () {
    $report = new UsageReport(
        events: 1,
        byType: ['search' => 1],
        byDay: [],
        hits: 0,
        misses: 1,
        topNodes: [],
        missedSubjects: ['x</script>y' => 1],
        emptySearches: [],
        charsServed: 0,
        distinctNodesConsulted: 0,
        malformedLines: 0,
        newerSchemaLines: 0,
        windowDays: null,
    );

    $html = (new UsagePageRenderer)($report);

    expect($html)->not->toContain('x</script>y')
        ->and($html)->toContain('x\\u003C/script\\u003Ey');
});

test('empty collections render as JSON objects, not arrays', function () {
    $report = new UsageReport(
        events: 0,
        byType: [],
        byDay: [],
        hits: 0,
        misses: 0,
        topNodes: [],
        missedSubjects: [],
        emptySearches: [],
        charsServed: 0,
        distinctNodesConsulted: 0,
        malformedLines: 0,
        newerSchemaLines: 0,
        windowDays: null,
    );

    expect((new UsagePageRenderer)($report))->toContain('"top_nodes":{}');
});
