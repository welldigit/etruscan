<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Request;
use WellDigit\Etruscan\Mcp\Tools\LookupNode;
use WellDigit\Etruscan\Mcp\Tools\MapOverview;
use WellDigit\Etruscan\Mcp\Tools\SearchMap;
use WellDigit\Etruscan\Mcp\Tools\TraceNode;

beforeEach(function () {
    $this->vaultPath = sys_get_temp_dir().'/etruscan-mcp-'.uniqid();
    File::ensureDirectoryExists($this->vaultPath);

    File::put($this->vaultPath.'/monitor-create.md', implode("\n", [
        '---',
        'alias: monitor-create',
        'class: MonitorCreate',
        'fqcn: "App\\\\MonitorCreate"',
        'source: src/MonitorCreate.php',
        'layer: action',
        'context: monitor',
        'generated_by: etruscan',
        '---',
        '',
        '## Description',
        '',
        'Creates a monitor after guarding the plan cap.',
        '',
        '## References',
        '',
        '- [[monitor]]',
        '',
        'Human wisdom below.',
    ])."\n");
    File::put($this->vaultPath.'/monitor.md', implode("\n", [
        '---',
        'alias: monitor',
        'class: Monitor',
        'source: src/Monitor.php',
        'layer: model',
        'context: monitor',
        'generated_by: etruscan',
        '---',
        '',
        '## Description',
        '',
        'The uptime monitor aggregate.',
        '',
        '## Referenced by',
        '',
        '- [[monitor-create]]',
    ])."\n");

    config()->set('etruscan.vault_path', $this->vaultPath);
});

afterEach(function () {
    File::deleteDirectory($this->vaultPath);
});

function usageLines(string $vaultPath): array
{
    $logPath = $vaultPath.'/usage.jsonl';

    if (! File::exists($logPath)) {
        return [];
    }

    return array_map(
        static fn (string $line): mixed => json_decode($line, true),
        array_values(array_filter(explode("\n", File::get($logPath)))),
    );
}

test('the etruscan server is registered with the MCP registrar', function () {
    expect(Mcp::getLocalServer('etruscan'))->not->toBeNull();
});

test('lookup-node returns the full note and records a hit', function () {
    $response = app(LookupNode::class)->handle(new Request(['alias' => 'monitor-create']));
    $text = (string) $response->content();

    expect($text)->toContain('monitor-create')
        ->and($text)->toContain('Creates a monitor after guarding the plan cap.')
        ->and($text)->toContain('References: monitor')
        ->and($text)->toContain('Human wisdom below.')
        ->and($text)->toContain('src/MonitorCreate.php');

    $lines = usageLines($this->vaultPath);

    expect($lines)->toHaveCount(1)
        ->and($lines[0]['type'])->toBe('lookup')
        ->and($lines[0]['outcome'])->toBe('hit')
        ->and($lines[0]['subject'])->toBe('monitor-create');
});

test('lookup-node records a ground-truth miss with suggestions for an unknown alias', function () {
    $response = app(LookupNode::class)->handle(new Request(['alias' => 'monitor-creat']));
    $text = (string) $response->content();

    expect($text)->toContain('No node')
        ->and($text)->toContain('monitor-create');

    $lines = usageLines($this->vaultPath);

    expect($lines)->toHaveCount(1)
        ->and($lines[0]['outcome'])->toBe('miss')
        ->and($lines[0]['subject'])->toBe('monitor-creat');
});

test('search-map ranks matches and records the result count', function () {
    $response = app(SearchMap::class)->handle(new Request(['query' => 'monitor']));
    $text = (string) $response->content();

    expect($text)->toContain('monitor-create')
        ->and($text)->toContain('The uptime monitor aggregate.');

    $lines = usageLines($this->vaultPath);

    expect($lines[0]['type'])->toBe('search')
        ->and($lines[0]['outcome'])->toBe('hit')
        ->and($lines[0]['results'])->toBe(2);
});

test('search-map records a miss for a query nothing matches', function () {
    app(SearchMap::class)->handle(new Request(['query' => 'blockchain sharding']));

    $lines = usageLines($this->vaultPath);

    expect($lines[0]['outcome'])->toBe('miss')
        ->and($lines[0]['subject'])->toBe('blockchain sharding');
});

test('trace-node walks both directions with descriptions', function () {
    $response = app(TraceNode::class)->handle(new Request(['alias' => 'monitor']));
    $text = (string) $response->content();

    expect($text)->toContain('Referenced by (who uses it):')
        ->and($text)->toContain('monitor-create')
        ->and($text)->toContain('Creates a monitor after guarding the plan cap.');
});

test('map-overview groups nodes by axis', function () {
    $response = app(MapOverview::class)->handle(new Request([]));
    $text = (string) $response->content();

    expect($text)->toContain('2 nodes on the map')
        ->and($text)->toContain('grouped by [context]')
        ->and($text)->toContain('monitor-create');
});

test('disabled usage tracking answers normally but records nothing', function () {
    config()->set('etruscan.usage_tracking', false);

    $response = app(LookupNode::class)->handle(new Request(['alias' => 'monitor']));

    expect((string) $response->content())->toContain('The uptime monitor aggregate.')
        ->and(usageLines($this->vaultPath))->toBe([]);
});

test('an empty vault yields a generate hint, not a recorded event', function () {
    config()->set('etruscan.vault_path', $this->vaultPath.'-missing');

    $response = app(LookupNode::class)->handle(new Request(['alias' => 'anything']));

    expect((string) $response->content())->toContain('etruscan:generate')
        ->and(usageLines($this->vaultPath.'-missing'))->toBe([]);
});
