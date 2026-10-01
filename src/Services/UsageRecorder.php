<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Payloads\UsageEvent;
use WellDigit\Etruscan\Utilities\ReportsDirectoryPreparer;

#[\EtruscanNode('usage-recorder')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('usage')]
final readonly class UsageRecorder
{
    public const int SCHEMA_VERSION = 1;

    private const int SUBJECT_MAX_LENGTH = 200;

    /** A log at or past this size is rotated to `{log}.1` before the next append. */
    public const int MAX_LOG_BYTES = 5 * 1024 * 1024;

    public const string ROTATED_SUFFIX = '.1';

    private const string LOCK_SUFFIX = '.lock';

    public function __construct(
        private int $maxLogBytes = self::MAX_LOG_BYTES,
    ) {}

    /**
     * Appends one whole line under an exclusive lock, so concurrent local
     * agents interleave lines, never bytes. The log is rotated once it
     * reaches its cap, so measurement never grows without bound. Failures are swallowed — the
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

        $lock = null;

        try {
            ReportsDirectoryPreparer::prepare(dirname($logPath));

            // Rotation renames the log, so a lock on the log itself cannot guard
            // it: a second agent would hold the old inode. A sidecar lock
            // serialises the size check, the rename and the append, so two
            // agents at the cap cannot both rotate and wipe the kept generation.
            $lock = fopen($logPath.self::LOCK_SUFFIX, 'c');

            if ($lock === false || ! flock($lock, LOCK_EX)) {
                return;
            }

            clearstatcache(true, $logPath);

            // One previous generation is kept, so the report still spans the
            // rotation while the pair stays bounded at roughly twice the cap.
            if (File::isFile($logPath) && File::size($logPath) >= $this->maxLogBytes) {
                File::move($logPath, $logPath.self::ROTATED_SUFFIX);
            }

            File::append($logPath, $line.PHP_EOL);
        } catch (\Throwable) {
            // Recording is best-effort by design.
        } finally {
            if (is_resource($lock)) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }
}
