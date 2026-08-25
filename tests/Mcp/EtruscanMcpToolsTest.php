<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Request;
use WellDigit\Etruscan\Mcp\Services\MapReader;
use WellDigit\Etruscan\Mcp\Tools\LookupNode;
use WellDigit\Etruscan\Mcp\Tools\MapOverview;
use WellDigit\Etruscan\Mcp\Tools\NoteTrustReminder;
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

/**
 * @return list<array<string, mixed>>
 */
function usageLines(string $vaultPath): array
{
    $logPath = $vaultPath.'/.reports/usage.jsonl';

    if (! File::exists($logPath)) {
        return [];
    }

    return array_map(
        decodeJson(...),
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
        ->and($text)->toContain('src/MonitorCreate.php')
        ->and($text)->toContain(NoteTrustReminder::LINE);

    $lines = usageLines($this->vaultPath);

    expect($lines)->toHaveCount(1)
        ->and($lines[0]['type'])->toBe('lookup')
        ->and($lines[0]['outcome'])->toBe('hit')
        ->and($lines[0]['subject'])->toBe('monitor-create')
        ->and($lines[0]['chars'])->toBe(mb_strlen($text));
});

test('lookup-node records a ground-truth miss with suggestions for an unknown alias', function () {
    $response = app(LookupNode::class)->handle(new Request(['alias' => 'monitor-creat']));
    $text = (string) $response->content();

    expect($text)->toContain('No node')
        ->and($text)->toContain('monitor-create')
        ->and($text)->not->toContain(NoteTrustReminder::LINE);

    $lines = usageLines($this->vaultPath);

    expect($lines)->toHaveCount(1)
        ->and($lines[0]['outcome'])->toBe('miss')
        ->and($lines[0]['subject'])->toBe('monitor-creat')
        ->and($lines[0]['chars'])->toBe(mb_strlen($text));
});

test('search-map ranks matches and records the result count', function () {
    $response = app(SearchMap::class)->handle(new Request(['query' => 'monitor']));
    $text = (string) $response->content();

    expect($text)->toContain('monitor-create')
        ->and($text)->toContain('The uptime monitor aggregate.');

    $lines = usageLines($this->vaultPath);

    expect($lines[0]['type'])->toBe('search')
        ->and($lines[0]['outcome'])->toBe('hit')
        ->and($lines[0]['results'])->toBe(2)
        ->and($lines[0]['chars'])->toBe(mb_strlen($text));
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
        ->and($text)->toContain('Creates a monitor after guarding the plan cap.')
        ->and($text)->toContain(NoteTrustReminder::LINE);
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

// The paths an agent hits when it calls a tool wrong, or before the map exists.
// Each one's message is the only guidance it gets, so each one is asserted.

test('every tool declares its arguments, and the required ones are marked required', function () {
    $schemas = [
        LookupNode::class => ['alias'],
        SearchMap::class => ['query'],
        TraceNode::class => ['alias', 'direction'],
        MapOverview::class => ['axis'],
    ];

    foreach ($schemas as $tool => $arguments) {
        $properties = app($tool)->toArray()['inputSchema']['properties'] ?? [];

        expect($properties)->toBeArray()
            ->and(array_keys(is_array($properties) ? $properties : []))->toBe($arguments, $tool);
    }
});

test('a blank required argument is refused with a usable instruction', function () {
    expect((string) app(LookupNode::class)->handle(new Request(['alias' => '   ']))->content())
        ->toContain('Provide the node alias to look up.')
        ->and((string) app(SearchMap::class)->handle(new Request(['query' => '']))->content())
        ->toContain('Provide a search query.')
        ->and((string) app(TraceNode::class)->handle(new Request(['alias' => '']))->content())
        ->toContain('Provide the node alias to trace.');
});

test('an unknown trace direction names the three that work', function () {
    $text = (string) app(TraceNode::class)->handle(new Request(['alias' => 'monitor', 'direction' => 'sideways']))
        ->content();

    expect($text)->toContain('out, in, both');
});

test('trace-node records a miss and points at search-map when the alias is unknown', function () {
    $response = app(TraceNode::class)->handle(new Request(['alias' => 'nope']));
    $lines = usageLines($this->vaultPath);

    expect((string) $response->content())->toContain('use search-map to find the right alias')
        ->and($lines[0]['type'])->toBe('trace')
        ->and($lines[0]['outcome'])->toBe('miss')
        ->and($lines[0]['results'])->toBe(0);
});

test('every tool says the same thing when there is no map yet', function () {
    config()->set('etruscan.vault_path', $this->vaultPath.'-empty');

    $requests = [
        LookupNode::class => ['alias' => 'monitor'],
        SearchMap::class => ['query' => 'monitor'],
        TraceNode::class => ['alias' => 'monitor'],
        MapOverview::class => [],
    ];

    foreach ($requests as $tool => $arguments) {
        expect((string) app($tool)->handle(new Request($arguments))->content())
            ->toContain(MapReader::EMPTY_MAP_MESSAGE);
    }
});

test('a long description is truncated in search results so one node cannot crowd out the rest', function () {
    File::put($this->vaultPath.'/verbose.md', implode("\n", [
        '---',
        'alias: verbose',
        'class: Verbose',
        'source: src/Verbose.php',
        'generated_by: etruscan',
        '---',
        '',
        '## Description',
        '',
        str_repeat('long ', 60),
    ])."\n");

    $text = (string) app(SearchMap::class)->handle(new Request(['query' => 'verbose']))->content();

    expect($text)->toContain('...')
        ->and(mb_strlen($text))->toBeLessThan(300);
});

test('a miss with nothing close falls back to the annotate instruction alone', function () {
    $text = (string) app(LookupNode::class)->handle(new Request(['alias' => 'zzzzzzzz']))->content();

    expect($text)->toContain('#[EtruscanNode]')
        ->and($text)->not->toContain('Closest aliases');
});
