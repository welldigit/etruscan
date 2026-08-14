<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\UsageEventType;
use WellDigit\Etruscan\Enums\UsageOutcome;
use WellDigit\Etruscan\Payloads\UsageEvent;

#[EtruscanNode('usage-log-reader')]
#[EtruscanLayer('service')]
#[EtruscanContext('usage')]
final readonly class UsageLogReader
{
    /**
     * Tolerant by design: malformed lines and lines written by a newer
     * schema are skipped and counted, never fatal.
     *
     * @return array{events: list<UsageEvent>, malformed: int, newerSchema: int}
     */
    public function __invoke(string $logPath): array
    {
        if (! File::exists($logPath)) {
            return ['events' => [], 'malformed' => 0, 'newerSchema' => 0];
        }

        $events = [];
        $malformed = 0;
        $newerSchema = 0;

        foreach (preg_split('/\R/', File::get($logPath)) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }

            $row = json_decode($line, true);

            if (! is_array($row)) {
                $malformed++;

                continue;
            }

            if (is_int($row['v'] ?? null) && $row['v'] > UsageRecorder::SCHEMA_VERSION) {
                $newerSchema++;

                continue;
            }

            $event = $this->hydrate($row);

            if ($event === null) {
                $malformed++;

                continue;
            }

            $events[] = $event;
        }

        return ['events' => $events, 'malformed' => $malformed, 'newerSchema' => $newerSchema];
    }

    /**
     * @param  array<array-key, mixed>  $row
     */
    private function hydrate(array $row): ?UsageEvent
    {
        $type = is_string($row['type'] ?? null) ? UsageEventType::tryFrom($row['type']) : null;
        $outcome = is_string($row['outcome'] ?? null) ? UsageOutcome::tryFrom($row['outcome']) : null;
        $subject = $row['subject'] ?? null;
        $recordedAt = $row['recorded_at'] ?? null;

        if ($type === null || $outcome === null || ! is_string($subject)) {
            return null;
        }

        if (! is_string($recordedAt) || strtotime($recordedAt) === false) {
            return null;
        }

        return new UsageEvent(
            type: $type,
            outcome: $outcome,
            subject: $subject,
            results: is_numeric($row['results'] ?? null) ? (int) $row['results'] : 0,
            chars: is_numeric($row['chars'] ?? null) ? (int) $row['chars'] : 0,
            recordedAt: $recordedAt,
        );
    }
}
