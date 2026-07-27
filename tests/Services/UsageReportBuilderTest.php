<?php

declare(strict_types=1);

use WellDigit\Etruscan\Enums\UsageEventType;
use WellDigit\Etruscan\Enums\UsageOutcome;
use WellDigit\Etruscan\Payloads\UsageEvent;
use WellDigit\Etruscan\Services\UsageReportBuilder;

function reportEvent(string $type, string $outcome, string $subject, string $recordedAt = '2026-07-27T10:00:00+00:00'): UsageEvent
{
    return new UsageEvent(
        type: UsageEventType::from($type),
        outcome: UsageOutcome::from($outcome),
        subject: $subject,
        results: 1,
        recordedAt: $recordedAt,
    );
}

test('totals, hit and miss counts, top nodes and miss subjects aggregate correctly', function () {
    $report = (new UsageReportBuilder)(
        events: [
            reportEvent(type: 'lookup', outcome: 'hit', subject: 'monitor'),
            reportEvent(type: 'lookup', outcome: 'hit', subject: 'monitor'),
            reportEvent(type: 'trace', outcome: 'hit', subject: 'billing'),
            reportEvent(type: 'lookup', outcome: 'miss', subject: 'plan-cap'),
            reportEvent(type: 'search', outcome: 'miss', subject: 'quota logic'),
            reportEvent(type: 'overview', outcome: 'hit', subject: 'context'),
        ],
        malformedLines: 2,
        newerSchemaLines: 1,
    );

    expect($report->events)->toBe(6)
        ->and($report->hits)->toBe(4)
        ->and($report->misses)->toBe(2)
        ->and($report->byType)->toBe(['lookup' => 3, 'trace' => 1, 'search' => 1, 'overview' => 1])
        ->and($report->topNodes)->toBe(['monitor' => 2, 'billing' => 1])
        ->and($report->missedSubjects)->toBe(['plan-cap' => 1, 'quota logic' => 1])
        ->and($report->emptySearches)->toBe(['quota logic' => 1])
        ->and($report->distinctNodesConsulted)->toBe(2)
        ->and($report->malformedLines)->toBe(2)
        ->and($report->newerSchemaLines)->toBe(1);
});

test('events aggregate per day, chronologically, with the daily miss count', function () {
    $report = (new UsageReportBuilder)(
        events: [
            reportEvent(type: 'lookup', outcome: 'hit', subject: 'b', recordedAt: '2026-07-27T10:00:00+00:00'),
            reportEvent(type: 'lookup', outcome: 'miss', subject: 'x', recordedAt: '2026-07-26T09:00:00+00:00'),
            reportEvent(type: 'lookup', outcome: 'hit', subject: 'a', recordedAt: '2026-07-26T08:00:00+00:00'),
        ],
        malformedLines: 0,
        newerSchemaLines: 0,
    );

    expect($report->byDay)->toBe([
        '2026-07-26' => ['events' => 2, 'misses' => 1],
        '2026-07-27' => ['events' => 1, 'misses' => 0],
    ]);
});

test('a since cutoff excludes older events', function () {
    $report = (new UsageReportBuilder)(
        events: [
            reportEvent(type: 'lookup', outcome: 'hit', subject: 'old', recordedAt: '2026-07-01T10:00:00+00:00'),
            reportEvent(type: 'lookup', outcome: 'hit', subject: 'new', recordedAt: '2026-07-27T10:00:00+00:00'),
        ],
        malformedLines: 0,
        newerSchemaLines: 0,
        sinceTimestamp: (int) strtotime('2026-07-20T00:00:00+00:00'),
        windowDays: 7,
    );

    expect($report->events)->toBe(1)
        ->and($report->topNodes)->toBe(['new' => 1])
        ->and($report->windowDays)->toBe(7);
});

test('overview and search hits do not pollute the top consulted nodes', function () {
    $report = (new UsageReportBuilder)(
        events: [
            reportEvent(type: 'overview', outcome: 'hit', subject: 'context'),
            reportEvent(type: 'search', outcome: 'hit', subject: 'booking'),
        ],
        malformedLines: 0,
        newerSchemaLines: 0,
    );

    expect($report->topNodes)->toBe([])
        ->and($report->distinctNodesConsulted)->toBe(0);
});
