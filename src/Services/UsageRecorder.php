<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Payloads\UsageEvent;
use WellDigit\Etruscan\Utilities\ReportsDirectoryPreparer;

#[EtruscanNode('usage-recorder')]
#[EtruscanLayer('service')]
#[EtruscanContext('usage')]
final readonly class UsageRecorder
{
    public const int SCHEMA_VERSION = 1;

    private const int SUBJECT_MAX_LENGTH = 200;

    /**
     * Appends one whole line under an exclusive lock, so concurrent local
     * agents interleave lines, never bytes. Failures are swallowed — the
     * measurement must never break the tool that is being measured.
     */
    public function __invoke(string $logPath, UsageEvent $usageEvent): void
    {
        $line = json_encode([
            'v' => self::SCHEMA_VERSION,
            'recorded_at' => $usageEvent->recordedAt,
            'type' => $usageEvent->type->value,
            'outcome' => $usageEvent->outcome->value,
            'subject' => mb_substr($usageEvent->subject, 0, self::SUBJECT_MAX_LENGTH),
            'results' => $usageEvent->results,
            'chars' => $usageEvent->chars,
        ]);

        if ($line === false) {
            return;
        }

        try {
            ReportsDirectoryPreparer::prepare(dirname($logPath));
            File::append($logPath, $line.PHP_EOL, lock: true);
        } catch (\Throwable) {
            // Recording is best-effort by design.
        }
    }
}
