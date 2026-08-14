<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Payloads;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('usage-report')]
#[EtruscanLayer('payload')]
#[EtruscanContext('usage')]
final readonly class UsageReport
{
    /**
     * @param  int  $events  Events inside the window.
     * @param  array<string, int>  $byType  Event counts per tool.
     * @param  array<string, array{events: int, misses: int}>  $byDay  date => daily totals, chronological.
     * @param  array<string, int>  $topNodes  alias => consultations, ranked.
     * @param  array<string, int>  $missedSubjects  wanted alias/query => times asked, ranked.
     * @param  array<string, int>  $emptySearches  query => times it returned nothing.
     * @param  int  $charsServed  Total characters the map served across all events.
     * @param  int|null  $windowDays  Null when the whole log was read.
     */
    public function __construct(
        public int $events,
        public array $byType,
        public array $byDay,
        public int $hits,
        public int $misses,
        public array $topNodes,
        public array $missedSubjects,
        public array $emptySearches,
        public int $charsServed,
        public int $distinctNodesConsulted,
        public int $malformedLines,
        public int $newerSchemaLines,
        public ?int $windowDays,
    ) {}
}
