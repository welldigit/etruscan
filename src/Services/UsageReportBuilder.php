<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\UsageEventType;
use WellDigit\Etruscan\Enums\UsageOutcome;
use WellDigit\Etruscan\Payloads\UsageEvent;
use WellDigit\Etruscan\Payloads\UsageReport;

#[EtruscanNode('usage-report-builder')]
#[EtruscanLayer('service')]
#[EtruscanContext('usage')]
final readonly class UsageReportBuilder
{
    private const int TOP_LIMIT = 10;

    /**
     * Pure aggregation: the cutoff is passed in, never computed here.
     *
     * @param  list<UsageEvent>  $events
     */
    public function __invoke(
        array $events,
        int $malformedLines,
        int $newerSchemaLines,
        ?int $sinceTimestamp = null,
        ?int $windowDays = null,
    ): UsageReport {
        if ($sinceTimestamp !== null) {
            $events = array_values(array_filter(
                $events,
                static fn (UsageEvent $usageEvent): bool => strtotime($usageEvent->recordedAt) >= $sinceTimestamp,
            ));
        }

        $byType = [];
        $byDay = [];
        $hits = 0;
        $misses = 0;
        $nodeCounts = [];
        $missedSubjects = [];
        $emptySearches = [];

        foreach ($events as $usageEvent) {
            $byType[$usageEvent->type->value] = ($byType[$usageEvent->type->value] ?? 0) + 1;

            $day = date('Y-m-d', (int) strtotime($usageEvent->recordedAt));
            $byDay[$day] ??= ['events' => 0, 'misses' => 0];
            $byDay[$day]['events']++;

            if ($usageEvent->outcome === UsageOutcome::Miss) {
                $byDay[$day]['misses']++;
            }

            if ($usageEvent->outcome === UsageOutcome::Hit) {
                $hits++;

                if ($usageEvent->type === UsageEventType::Lookup || $usageEvent->type === UsageEventType::Trace) {
                    $nodeCounts[$usageEvent->subject] = ($nodeCounts[$usageEvent->subject] ?? 0) + 1;
                }

                continue;
            }

            $misses++;
            $missedSubjects[$usageEvent->subject] = ($missedSubjects[$usageEvent->subject] ?? 0) + 1;

            if ($usageEvent->type === UsageEventType::Search) {
                $emptySearches[$usageEvent->subject] = ($emptySearches[$usageEvent->subject] ?? 0) + 1;
            }
        }

        arsort($nodeCounts);
        arsort($missedSubjects);
        arsort($emptySearches);
        ksort($byDay);

        return new UsageReport(
            events: count($events),
            byType: $byType,
            byDay: $byDay,
            hits: $hits,
            misses: $misses,
            topNodes: array_slice($nodeCounts, 0, self::TOP_LIMIT, preserve_keys: true),
            missedSubjects: array_slice($missedSubjects, 0, self::TOP_LIMIT, preserve_keys: true),
            emptySearches: array_slice($emptySearches, 0, self::TOP_LIMIT, preserve_keys: true),
            distinctNodesConsulted: count($nodeCounts),
            malformedLines: $malformedLines,
            newerSchemaLines: $newerSchemaLines,
            windowDays: $windowDays,
        );
    }
}
