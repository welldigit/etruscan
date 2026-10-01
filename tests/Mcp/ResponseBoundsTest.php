<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use WellDigit\Etruscan\Mcp\Tools\LookupNode;
use WellDigit\Etruscan\Mcp\Tools\MapOverview;
use WellDigit\Etruscan\Mcp\Tools\SearchMap;
use WellDigit\Etruscan\Mcp\Tools\TraceNode;

/**
 * A vault built to be hostile in every direction a response can grow: one hub
 * every node points at, a group far past the member limit, and a note whose
 * human section is a pasted document.
 *
 * The point of this file is not any single number. It is that a served
 * response stays bounded when the map does not — the failure mode no substring
 * assertion can catch, and the one that shipped an 18,000-character trace.
 */
const HUB_NEIGHBOURS = 200;

const LONG_MANUAL_CHARS = 50000;

beforeEach(function () {
    $this->vaultPath = sys_get_temp_dir().'/etruscan-bounds-'.uniqid();
    File::ensureDirectoryExists($this->vaultPath);

    // Every description is long and carries em dashes, so a byte-based clip
    // anywhere on the serving path shows up as invalid UTF-8 here.
    $description = 'A deliberately long specification — '.str_repeat('stating a rule the reader must not skim past — ', 6);

    $backlinks = [];

    foreach (range(1, HUB_NEIGHBOURS) as $n) {
        $alias = 'node-'.$n;
        $backlinks[] = '- [['.$alias.']]';

        File::put($this->vaultPath.'/'.$alias.'.md', implode("\n", [
            '---',
            'alias: '.$alias,
            'class: Node'.$n,
            'source: src/Node'.$n.'.php',
            'layer: service',
            'context: bulk',
            'generated_by: etruscan',
            '---',
            '',
            '## Description',
            '',
            $description,
            '',
            '## References',
            '',
            '- [[hub]]',
        ])."\n");
    }

    File::put($this->vaultPath.'/hub.md', implode("\n", array_merge([
        '---',
        'alias: hub',
        'class: Hub',
        'source: src/Hub.php',
        'layer: service',
        'context: bulk',
        'generated_by: etruscan',
        '---',
        '',
        '## Description',
        '',
        $description,
        '',
        '## Referenced by',
        '',
    ], $backlinks))."\n");

    File::put($this->vaultPath.'/verbose.md', implode("\n", [
        '---',
        'alias: verbose',
        'class: Verbose',
        'source: src/Verbose.php',
        'layer: service',
        'context: bulk',
        'generated_by: etruscan',
        '---',
        '',
        '## Description',
        '',
        $description,
        '',
        '## References',
        '',
        '- [[hub]]',
        '',
        // A pasted design doc, which is what the manual section has no
        // structural defence against.
        implode("\n", array_fill(0, (int) (LONG_MANUAL_CHARS / 50), str_repeat('x', 48))),
    ])."\n");

    config()->set('etruscan.vault_path', $this->vaultPath);
    config()->set('etruscan.usage_tracking', false);
});

afterEach(function () {
    File::deleteDirectory($this->vaultPath);
});

/**
 * @return array<string, string>
 */
function servedResponses(): array
{
    return [
        'map-overview' => (string) app(MapOverview::class)->handle(new Request([]))->content(),
        'map-overview group' => (string) app(MapOverview::class)->handle(new Request(['group' => 'bulk']))->content(),
        'search-map' => (string) app(SearchMap::class)->handle(new Request(['query' => 'specification']))->content(),
        'lookup-node' => (string) app(LookupNode::class)->handle(new Request(['alias' => 'verbose']))->content(),
        'trace-node' => (string) app(TraceNode::class)->handle(new Request(['alias' => 'hub']))->content(),
    ];
}

test('no tool serves an unbounded response, however hostile the map', function () {
    // Generous ceilings: they are not tuned targets, they are the line past
    // which a response has stopped being a summary of the map.
    $ceilings = [
        'map-overview' => 2000,
        'map-overview group' => 6000,
        'search-map' => 4000,
        'lookup-node' => 4000,
        'trace-node' => 12000,
    ];

    foreach (servedResponses() as $tool => $text) {
        expect(mb_strlen($text))->toBeLessThan($ceilings[$tool], $tool);
    }
});

test('every served response is valid UTF-8 and survives JSON encoding', function () {
    foreach (servedResponses() as $tool => $text) {
        expect(mb_check_encoding($text, 'UTF-8'))->toBeTrue($tool)
            ->and(json_encode(['text' => $text], JSON_UNESCAPED_UNICODE))->not->toBeFalse($tool);
    }
});

test('a hub trace keeps every edge and budgets only the prose', function () {
    $text = (string) app(TraceNode::class)->handle(new Request(['alias' => 'hub']))->content();

    // The blast radius is never truncated: a trace that shows 12 of 200
    // callers is not a shorter answer, it is a wrong one.
    foreach ([1, 99, HUB_NEIGHBOURS] as $n) {
        expect($text)->toContain('- node-'.$n);
    }

    // Count inlined lines, not em dashes — the descriptions contain their own.
    expect(preg_match_all('/^- \S+ — /m', $text))->toBe(8)
        ->and($text)->toContain('Showing 8 of '.HUB_NEIGHBOURS.' neighbour descriptions');
});

test('descriptions=0 returns the edges alone', function () {
    $text = (string) app(TraceNode::class)->handle(new Request(['alias' => 'hub', 'descriptions' => 0]))->content();

    expect($text)->toContain('- node-1')
        ->and($text)->not->toContain(' — A deliberately long specification')
        ->and(mb_strlen($text))->toBeLessThan(6000);
});

test('a group past the member limit reports its size and how to expand it', function () {
    $text = (string) app(MapOverview::class)->handle(new Request([]))->content();

    expect($text)->toContain('bulk ('.(HUB_NEIGHBOURS + 2).')')
        ->and($text)->toContain('call map-overview again with group="bulk"')
        ->and($text)->not->toContain('- node-1');

    $expanded = (string) app(MapOverview::class)->handle(new Request(['group' => 'bulk']))->content();

    expect($expanded)->toContain('- node-1')
        ->and($expanded)->toContain('- hub');
});

test('an oversized human-notes section is clipped and names the file holding the rest', function () {
    $text = (string) app(LookupNode::class)->handle(new Request(['alias' => 'verbose']))->content();

    expect($text)->toContain('Human notes (protected knowledge):')
        ->and($text)->toContain('characters of human notes')
        ->and($text)->toContain('verbose.md')
        ->and($text)->toContain('A deliberately long specification');
});
