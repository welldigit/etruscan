<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Enums\UsageEventType;
use WellDigit\Etruscan\Services\UsageLogReader;

beforeEach(function () {
    $this->logDirectory = sys_get_temp_dir().'/etruscan-logreader-'.uniqid();
    $this->logPath = $this->logDirectory.'/usage.jsonl';
    File::ensureDirectoryExists($this->logDirectory);
});

afterEach(function () {
    File::deleteDirectory($this->logDirectory);
});

test('an absent log yields an empty result', function () {
    expect(app(UsageLogReader::class)($this->logPath))->toBe(['events' => [], 'malformed' => 0, 'newerSchema' => 0]);
});

test('well-formed lines parse into events in file order', function () {
    File::put($this->logPath, implode("\n", [
        '{"v":1,"recorded_at":"2026-07-27T10:00:00+00:00","type":"lookup","outcome":"hit","subject":"monitor","results":1}',
        '{"v":1,"recorded_at":"2026-07-27T11:00:00+00:00","type":"search","outcome":"miss","subject":"plan cap","results":0}',
    ])."\n");

    $log = app(UsageLogReader::class)($this->logPath);

    expect($log['events'])->toHaveCount(2)
        ->and($log['events'][0]->subject)->toBe('monitor')
        ->and($log['events'][1]->type)->toBe(UsageEventType::Search)
        ->and($log['malformed'])->toBe(0);
});

test('garbage, wrong shapes, unknown enums and bad timestamps are skipped and counted', function () {
    File::put($this->logPath, implode("\n", [
        'not json at all',
        '"a json string, not an object"',
        '{"v":1,"recorded_at":"2026-07-27T10:00:00+00:00","type":"teleport","outcome":"hit","subject":"x","results":1}',
        '{"v":1,"recorded_at":"whenever","type":"lookup","outcome":"hit","subject":"x","results":1}',
        '{"v":1,"recorded_at":"2026-07-27T10:00:00+00:00","type":"lookup","outcome":"hit","subject":"good","results":1}',
        '',
    ])."\n");

    $log = app(UsageLogReader::class)($this->logPath);

    expect($log['events'])->toHaveCount(1)
        ->and($log['events'][0]->subject)->toBe('good')
        ->and($log['malformed'])->toBe(4);
});

test('lines from a newer schema are counted separately, not as malformed', function () {
    File::put($this->logPath, implode("\n", [
        '{"v":2,"some_future_field":"x"}',
        '{"v":1,"recorded_at":"2026-07-27T10:00:00+00:00","type":"lookup","outcome":"hit","subject":"good","results":1}',
    ])."\n");

    $log = app(UsageLogReader::class)($this->logPath);

    expect($log['events'])->toHaveCount(1)
        ->and($log['newerSchema'])->toBe(1)
        ->and($log['malformed'])->toBe(0);
});

test('the rotated generation is read first, so a rotation does not empty the report', function () {
    File::put($this->logPath.'.1', '{"v":1,"recorded_at":"2026-07-27T10:00:00+00:00","type":"lookup","outcome":"hit","subject":"older","results":1}'."\n");
    File::put($this->logPath, '{"v":1,"recorded_at":"2026-07-27T11:00:00+00:00","type":"lookup","outcome":"hit","subject":"newer","results":1}'."\n");

    $log = app(UsageLogReader::class)($this->logPath);

    expect(array_map(fn ($event) => $event->subject, $log['events']))->toBe(['older', 'newer'])
        ->and($log['malformed'])->toBe(0);
});
