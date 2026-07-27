<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->vaultPath = sys_get_temp_dir().'/etruscan-usagecmd-'.uniqid();
    File::ensureDirectoryExists($this->vaultPath);
    config()->set('etruscan.vault_path', $this->vaultPath);
});

afterEach(function () {
    File::deleteDirectory($this->vaultPath);
    Carbon::setTestNow();
});

function seedUsageLog(string $vaultPath, array $lines): void
{
    File::put($vaultPath.'/usage.jsonl', implode("\n", $lines)."\n");
}

test('no log yet reports that plainly and succeeds', function () {
    $this->artisan('etruscan:usage')
        ->expectsOutputToContain('No usage recorded yet')
        ->assertSuccessful();
});

test('a seeded log aggregates into the text report', function () {
    seedUsageLog($this->vaultPath, [
        '{"v":1,"recorded_at":"2026-07-27T10:00:00+00:00","type":"lookup","outcome":"hit","subject":"monitor","results":1}',
        '{"v":1,"recorded_at":"2026-07-27T10:05:00+00:00","type":"lookup","outcome":"hit","subject":"monitor","results":1}',
        '{"v":1,"recorded_at":"2026-07-27T10:10:00+00:00","type":"lookup","outcome":"miss","subject":"plan-cap","results":0}',
        'garbage line',
    ]);

    $this->artisan('etruscan:usage')
        ->expectsOutputToContain('3 consultation(s), 2 hit(s), 1 miss(es)')
        ->expectsOutputToContain('Most consulted nodes')
        ->expectsOutputToContain('plan-cap')
        ->expectsOutputToContain('annotation candidates')
        ->expectsOutputToContain('1 malformed')
        ->assertSuccessful();
});

test('the days window excludes older events', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-27T12:00:00+00:00'));

    seedUsageLog($this->vaultPath, [
        '{"v":1,"recorded_at":"2026-07-01T10:00:00+00:00","type":"lookup","outcome":"hit","subject":"old","results":1}',
        '{"v":1,"recorded_at":"2026-07-26T10:00:00+00:00","type":"lookup","outcome":"hit","subject":"new","results":1}',
    ]);

    $this->artisan('etruscan:usage', ['--days' => 7])
        ->expectsOutputToContain('1 consultation(s)')
        ->expectsOutputToContain('new')
        ->assertSuccessful();
});

test('the json output is a machine-readable contract', function () {
    seedUsageLog($this->vaultPath, [
        '{"v":1,"recorded_at":"2026-07-27T10:00:00+00:00","type":"search","outcome":"miss","subject":"quota logic","results":0}',
    ]);

    $exitCode = Artisan::call('etruscan:usage', ['--json' => true]);
    $decoded = json_decode(trim(Artisan::output()), true);

    expect($exitCode)->toBe(0);

    expect($decoded)->toHaveKeys(['window_days', 'events', 'by_type', 'by_day', 'hits', 'misses', 'top_nodes', 'missed_subjects', 'empty_searches', 'distinct_nodes_consulted', 'skipped'])
        ->and($decoded['events'])->toBe(1)
        ->and($decoded['by_day'])->toBe(['2026-07-27' => ['events' => 1, 'misses' => 1]])
        ->and($decoded['empty_searches'])->toBe(['quota logic' => 1]);
});

test('the html option renders the dashboard into the vault', function () {
    seedUsageLog($this->vaultPath, [
        '{"v":1,"recorded_at":"2026-07-27T10:00:00+00:00","type":"lookup","outcome":"hit","subject":"monitor","results":1}',
    ]);

    $this->artisan('etruscan:usage', ['--html' => true])
        ->expectsOutputToContain('usage.html')
        ->assertSuccessful();

    $html = File::get($this->vaultPath.'/usage.html');

    expect($html)->toContain('"events":1')
        ->and($html)->toContain('"top_nodes":{"monitor":1}');
});

test('the output option overrides the dashboard location', function () {
    seedUsageLog($this->vaultPath, [
        '{"v":1,"recorded_at":"2026-07-27T10:00:00+00:00","type":"lookup","outcome":"hit","subject":"monitor","results":1}',
    ]);

    $outputPath = $this->vaultPath.'/reports/usage-dashboard.html';

    $this->artisan('etruscan:usage', ['--html' => true, '--output' => $outputPath])->assertSuccessful();

    expect(File::exists($outputPath))->toBeTrue()
        ->and(File::exists($this->vaultPath.'/usage.html'))->toBeFalse();
});

test('an invalid days option fails loud', function () {
    $this->artisan('etruscan:usage', ['--days' => 'abc'])->assertFailed();
    $this->artisan('etruscan:usage', ['--days' => 0])->assertFailed();
});
