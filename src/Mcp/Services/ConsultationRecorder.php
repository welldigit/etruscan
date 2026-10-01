<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Services;

use WellDigit\Etruscan\Enums\UsageEventType;
use WellDigit\Etruscan\Enums\UsageOutcome;
use WellDigit\Etruscan\Payloads\UsageEvent;
use WellDigit\Etruscan\Services\UsageRecorder;
use WellDigit\Etruscan\Utilities\EtruscanConfig;
use WellDigit\Etruscan\Utilities\UsageLogPathResolver;

/**
 * One place where a map consultation becomes a usage event: the tracking gate,
 * the log path, the timestamp, and the size of the answer. Tools hand over the
 * text they are about to serve rather than a count, so the recorded size is by
 * construction the text that actually left the tool.
 */
#[\EtruscanNode('consultation-recorder')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('mcp')]
#[\EtruscanContext('usage')]
final readonly class ConsultationRecorder
{
    public function __construct(
        private UsageRecorder $usageRecorder,
    ) {}

    public function __invoke(
        string $vaultPath,
        UsageEventType $type,
        UsageOutcome $outcome,
        string $subject,
        int $results,
        string $servedText,
    ): void {
        if (! EtruscanConfig::usageTracking()) {
            return;
        }

        ($this->usageRecorder)(UsageLogPathResolver::resolve($vaultPath), new UsageEvent(
            type: $type,
            outcome: $outcome,
            subject: $subject,
            results: $results,
            chars: mb_strlen($servedText),
            recordedAt: now()->toIso8601String(),
        ));
    }
}
