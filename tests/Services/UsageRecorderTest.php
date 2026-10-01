<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Enums\UsageEventType;
use WellDigit\Etruscan\Enums\UsageOutcome;
use WellDigit\Etruscan\Payloads\UsageEvent;
use WellDigit\Etruscan\Services\UsageRecorder;

beforeEach(function () {
    $this->logDirectory = sys_get_temp_dir().'/etruscan-recorder-'.uniqid();
    $this->logPath = $this->logDirectory.'/usage.jsonl';
});

afterEach(function () {
    File::deleteDirectory($this->logDirectory);
});

function usageEvent(string $subject = 'monitor', string $outcome = 'hit'): UsageEvent
{
    return new UsageEvent(
        type: UsageEventType::Lookup,
        outcome: UsageOutcome::from($outcome),
        subject: $subject,
        results: 1,
        chars: 420,
        recordedAt: '2026-07-27T10:00:00+00:00',
    );
}

test('the first record creates the directory and file with one valid JSON line', function () {
    app(UsageRecorder::class)($this->logPath, usageEvent());

    $lines = array_filter(explode("\n", File::get($this->logPath)));
    $row = json_decode($lines[0], true);

    expect($lines)->toHaveCount(1)
        ->and($row)->toBe([
            'v' => 1,
            'recorded_at' => '2026-07-27T10:00:00+00:00',
            'type' => 'lookup',
            'outcome' => 'hit',
            'subject' => 'monitor',
            'results' => 1,
            'chars' => 420,
        ]);
});

test('the log directory is seeded with a self-ignoring gitignore so usage data stays out of git by default', function () {
    app(UsageRecorder::class)($this->logPath, usageEvent());

    expect(File::get($this->logDirectory.'/.gitignore'))->toBe("*\n!.gitignore\n");
});

test('an edited gitignore is never overwritten, so teams can opt into committing the log', function () {
    File::ensureDirectoryExists($this->logDirectory);
    File::put($this->logDirectory.'/.gitignore', "!usage.jsonl\n");

    app(UsageRecorder::class)($this->logPath, usageEvent());

    expect(File::get($this->logDirectory.'/.gitignore'))->toBe("!usage.jsonl\n");
});

test('an oversized subject is truncated so one event cannot bloat the log', function () {
    app(UsageRecorder::class)($this->logPath, new UsageEvent(
        type: UsageEventType::Lookup,
        outcome: UsageOutcome::Miss,
        subject: str_repeat('x', 5000),
        results: 0,
        chars: 96,
        recordedAt: '2026-07-27T10:00:00+00:00',
    ));

    $row = decodeJson(File::get($this->logPath));

    expect($row['subject'])->toBeString()
        ->and(mb_strlen(is_string($row['subject']) ? $row['subject'] : ''))->toBe(200);
});

test('appends accumulate as independently decodable lines', function () {
    $usageRecorder = app(UsageRecorder::class);
    $usageRecorder($this->logPath, usageEvent(subject: 'first'));
    $usageRecorder($this->logPath, usageEvent(subject: 'second', outcome: 'miss'));

    $rows = array_map(
        decodeJson(...),
        array_values(array_filter(explode("\n", File::get($this->logPath)))),
    );

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['subject'])->toBe('first')
        ->and($rows[1]['subject'])->toBe('second')
        ->and($rows[1]['outcome'])->toBe('miss');
});

test('a log at its cap rotates to one previous generation before the next append', function () {
    $usageRecorder = new UsageRecorder(maxLogBytes: 100);

    $usageRecorder($this->logPath, usageEvent(subject: 'first'));
    $usageRecorder($this->logPath, usageEvent(subject: 'second'));
    $usageRecorder($this->logPath, usageEvent(subject: 'third'));

    expect(File::get($this->logPath.UsageRecorder::ROTATED_SUFFIX))->toContain('"subject":"second"')
        ->and(File::get($this->logPath))->toContain('"subject":"third"')
        ->and(File::get($this->logPath))->not->toContain('"subject":"second"')
        ->and(File::exists($this->logPath.'.2'))->toBeFalse();
});

test('recording goes through a sidecar lock that survives rotation', function () {
    $usageRecorder = new UsageRecorder(maxLogBytes: 100);

    $usageRecorder($this->logPath, usageEvent(subject: 'first'));
    $usageRecorder($this->logPath, usageEvent(subject: 'second'));

    expect(File::exists($this->logPath.'.lock'))->toBeTrue()
        ->and(File::get($this->logPath.UsageRecorder::ROTATED_SUFFIX))->toContain('"subject":"first"')
        ->and(File::get($this->logPath))->toContain('"subject":"second"');
});
