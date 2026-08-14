<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Boost;
use Laravel\Boost\Mcp\ToolRegistry;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use WellDigit\Etruscan\Mcp\Tools\LookupNode;
use WellDigit\Etruscan\Mcp\Tools\MapOverview;
use WellDigit\Etruscan\Mcp\Tools\SearchMap;
use WellDigit\Etruscan\Mcp\Tools\TraceNode;

beforeEach(function () {
    $this->mapTools = [MapOverview::class, SearchMap::class, LookupNode::class, TraceNode::class];

    ToolRegistry::clearCache();
});

test('the four map tools are added to Boost\'s tool include list', function () {
    $include = (array) config('boost.mcp.tools.include');

    foreach ($this->mapTools as $mapTool) {
        expect($include)->toContain($mapTool);
    }
});

test('re-registering does not duplicate entries', function () {
    $include = array_filter((array) config('boost.mcp.tools.include'), is_string(...));

    expect(array_count_values($include)[LookupNode::class])->toBe(1);
});

test('Boost advertises the map tools on its own server, so no .mcp.json entry is needed', function () {
    $boost = new Boost(new FakeTransporter);
    $boost->start();

    // The class list, not resolved instances: Boost's own tools pull bindings
    // that only a Boost-installed app has, and what is under test is whether
    // the include list reached Boost's discovery at all.
    $advertised = new ReflectionProperty(Boost::class, 'tools')->getValue($boost);

    foreach ($this->mapTools as $mapTool) {
        expect($advertised)->toContain($mapTool);
    }
});

test('Boost allows the map tools to be executed, not just listed', function () {
    foreach ($this->mapTools as $mapTool) {
        expect(ToolRegistry::isToolAllowed($mapTool))->toBeTrue();
    }
});
