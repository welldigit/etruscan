<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Payloads;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\UsageEventType;
use WellDigit\Etruscan\Enums\UsageOutcome;

#[EtruscanNode('usage-event')]
#[EtruscanLayer('payload')]
#[EtruscanContext('usage')]
final readonly class UsageEvent
{
    /**
     * @param  UsageEventType  $type  Which tool answered.
     * @param  UsageOutcome  $outcome  Whether the map could answer.
     * @param  string  $subject  The alias or query the agent asked for.
     * @param  int  $results  How many nodes the answer carried.
     * @param  int  $chars  Size of the served answer in characters.
     * @param  string  $recordedAt  ISO-8601 timestamp, stamped server-side.
     */
    public function __construct(
        public UsageEventType $type,
        public UsageOutcome $outcome,
        public string $subject,
        public int $results,
        public int $chars,
        public string $recordedAt,
    ) {}
}
