<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Utilities\NodeSummaryLine;

beforeEach(function () {
    $this->vaultPath = sys_get_temp_dir().'/etruscan-export-'.uniqid();
    File::ensureDirectoryExists($this->vaultPath);
    config()->set('etruscan.vault_path', $this->vaultPath);

    File::put($this->vaultPath.'/monitor.md', implode("\n", [
        '---', 'alias: monitor', 'source: src/Monitor.php', 'context: monitor', 'generated_by: etruscan', '---',
        '', '## Description', '', 'The uptime monitor aggregate.',
    ])."\n");
    File::put($this->vaultPath.'/invoice.md', implode("\n", [
        '---', 'alias: invoice', 'source: src/Invoice.php', 'context: billing', 'generated_by: etruscan', '---',
        '', '## Description', '', 'A billable invoice.',
    ])."\n");
});

afterEach(function () {
    File::deleteDirectory($this->vaultPath);
});

test('the index is written to the vault root and the import line is printed', function () {
    Artisan::call('etruscan:export');
    $output = Artisan::output();

    expect(File::exists($this->vaultPath.'/map.md'))->toBeTrue()
        ->and($output)->toContain('Exported 2 node(s)')
        ->and($output)->toContain('Add this line to CLAUDE.md')
        ->and($output)->toContain('map.md');
});

// .reports/ seeds a .gitignore so its contents stay out of git. The index is
// the one artifact meant to be committed, so it must not land there.
test('the index is not filed with the machine-local reports', function () {
    $this->artisan('etruscan:export')->assertSuccessful();

    expect(File::exists($this->vaultPath.'/.reports/map.md'))->toBeFalse()
        ->and(File::exists($this->vaultPath.'/.reports/.gitignore'))->toBeFalse();
});

test('the written index carries every node, grouped', function () {
    $this->artisan('etruscan:export')->assertSuccessful();
    $digest = File::get($this->vaultPath.'/map.md');

    expect($digest)->toContain('## billing')
        ->and($digest)->toContain('## monitor')
        ->and($digest)->toContain('- monitor (src/Monitor.php) — The uptime monitor aggregate.')
        ->and($digest)->toContain('- invoice (src/Invoice.php) — A billable invoice.');
});

test('stdout prints the index and writes nothing', function () {
    Artisan::call('etruscan:export', ['--stdout' => true]);

    expect(Artisan::output())->toContain('# Codebase map')
        ->and(File::exists($this->vaultPath.'/map.md'))->toBeFalse();
});

test('the output option overrides the location', function () {
    $custom = $this->vaultPath.'/nested/elsewhere.md';

    $this->artisan('etruscan:export', ['--output' => $custom])->assertSuccessful();

    expect(File::exists($custom))->toBeTrue();
});

test('exporting an empty map fails loud rather than writing an empty index', function () {
    config()->set('etruscan.vault_path', $this->vaultPath.'-missing');

    $this->artisan('etruscan:export')
        ->expectsOutputToContain('etruscan:generate')
        ->assertFailed();
});

test('re-exporting an unchanged map rewrites the same bytes', function () {
    $this->artisan('etruscan:export')->assertSuccessful();
    $first = File::get($this->vaultPath.'/map.md');

    $this->artisan('etruscan:export')->assertSuccessful();

    expect(File::get($this->vaultPath.'/map.md'))->toBe($first);
});

// The guard that actually catches re-inflation: a node's cost in the index is
// bounded by the clip, whatever its note says. A whole-file ratio would not —
// it moves with how densely linked the vault happens to be.
test('a node costs a bounded number of characters, however verbose its note', function () {
    File::put($this->vaultPath.'/verbose.md', implode("\n", array_merge([
        '---', 'alias: verbose', 'source: src/Verbose.php', 'context: monitor', 'generated_by: etruscan', '---',
        '', '## Description', '', str_repeat('A very long specification — ', 60),
        '', '## Referenced by', '',
    ], array_map(static fn (int $n): string => '- [[node-'.$n.']]', range(1, 100))))."\n");

    $this->artisan('etruscan:export')->assertSuccessful();

    $digest = File::get($this->vaultPath.'/map.md');
    $ceiling = NodeSummaryLine::DESCRIPTION_LIMIT + 120;

    foreach (explode("\n", $digest) as $line) {
        if (str_starts_with($line, '- ')) {
            expect(mb_strlen($line))->toBeLessThanOrEqual($ceiling, $line);
        }
    }

    expect(mb_strlen($digest))->toBeLessThan(mb_strlen(File::get($this->vaultPath.'/verbose.md')))
        ->and(mb_check_encoding($digest, 'UTF-8'))->toBeTrue();
});

test('the index is grouped by the configured group-by axis', function () {
    File::put($this->vaultPath.'/monitor.md', implode("\n", [
        '---', 'alias: monitor', 'source: src/Monitor.php', 'layer: model', 'context: monitor', 'generated_by: etruscan', '---',
        '', '## Description', '', 'The uptime monitor aggregate.',
    ])."\n");
    config()->set('etruscan.group_by', 'layer,context');

    $this->artisan('etruscan:export')->assertSuccessful();

    expect(File::get($this->vaultPath.'/map.md'))->toContain('grouped by [layer]')
        ->and(File::get($this->vaultPath.'/map.md'))->toContain('## model');
});

// The printed line only works if it is relative to the project root, because
// CLAUDE.md resolves an @import relative to itself.
test('the import line is project-relative when the vault lives in the project', function () {
    $inside = base_path('etruscan-export-inside');
    File::ensureDirectoryExists($inside);
    File::put($inside.'/monitor.md', "---\nalias: monitor\ncontext: monitor\ngenerated_by: etruscan\n---\n\n## Description\n\nIntent.\n");
    config()->set('etruscan.vault_path', $inside);

    Artisan::call('etruscan:export');
    $output = Artisan::output();

    File::deleteDirectory($inside);

    expect($output)->toContain('@etruscan-export-inside'.DIRECTORY_SEPARATOR.'map.md')
        ->and($output)->not->toContain('@'.base_path());
});
