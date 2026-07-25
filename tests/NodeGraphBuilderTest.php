<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use WellDigit\Etruscan\Exceptions\AliasCollisionException;
use WellDigit\Etruscan\Exceptions\ReservedAxisKeyException;
use WellDigit\Etruscan\Payloads\NoteContent;
use WellDigit\Etruscan\Payloads\ScannedClass;
use WellDigit\Etruscan\Services\NodeGraphBuilder;

function scannedNode(string $fqcn, ?string $alias, array $axes = [], array $references = [], ?string $extendsFqcn = null, ?string $description = null): ScannedClass
{
    return new ScannedClass(
        fqcn: $fqcn,
        axes: $axes,
        references: $references,
        sourcePath: '/src/'.str_replace('\\', '/', $fqcn).'.php',
        alias: $alias,
        extendsFqcn: $extendsFqcn,
        description: $description,
    );
}

test('only node classes become notes and references resolve to node aliases', function () {
    $notes = (new NodeGraphBuilder)([
        scannedNode(fqcn: 'App\\Monitor', alias: 'monitor', references: ['App\\Team', 'Vendor\\Framework\\Model']),
        scannedNode(fqcn: 'App\\Team', alias: 'team'),
        scannedNode(fqcn: 'App\\NotANode', alias: null, references: ['App\\Monitor']),
    ]);

    expect($notes)->toHaveCount(2)
        ->and($notes[0]->alias)->toBe('monitor')
        ->and($notes[0]->links)->toBe(['team'])
        ->and($notes[1]->alias)->toBe('team')
        ->and($notes[1]->links)->toBe([]);
});

test('notes are sorted by alias and links are sorted alphabetically', function () {
    $notes = (new NodeGraphBuilder)([
        scannedNode(fqcn: 'App\\Zulu', alias: 'zulu', references: ['App\\Mike', 'App\\Alpha']),
        scannedNode(fqcn: 'App\\Mike', alias: 'mike'),
        scannedNode(fqcn: 'App\\Alpha', alias: 'alpha'),
    ]);

    expect(array_map(fn (NoteContent $noteContent) => $noteContent->alias, $notes))->toBe(['alpha', 'mike', 'zulu'])
        ->and($notes[2]->links)->toBe(['alpha', 'mike']);
});

test('each node lists the nodes that reference it, sorted, under referencedBy', function () {
    $notes = (new NodeGraphBuilder)([
        scannedNode(fqcn: 'App\\Monitor', alias: 'monitor', references: ['App\\Team']),
        scannedNode(fqcn: 'App\\Incident', alias: 'incident', references: ['App\\Team']),
        scannedNode(fqcn: 'App\\Team', alias: 'team'),
    ]);

    $byAlias = collect($notes)->keyBy('alias');

    expect($byAlias['team']->referencedBy)->toBe(['incident', 'monitor'])   // both referrers, sorted
        ->and($byAlias['team']->links)->toBe([])                            // team references nothing
        ->and($byAlias['monitor']->referencedBy)->toBe([])                  // nothing references monitor
        ->and($byAlias['monitor']->links)->toBe(['team']);
});

test('a node never links to itself and leading backslashes on references are normalized', function () {
    $notes = (new NodeGraphBuilder)([
        scannedNode(fqcn: 'App\\Monitor', alias: 'monitor', references: ['App\\Monitor', '\\App\\Team']),
        scannedNode(fqcn: 'App\\Team', alias: 'team'),
    ]);

    expect($notes[0]->links)->toBe(['team']);
});

test('single-value axes render as scalars and multi-value axes as lists in frontmatter', function () {
    $notes = (new NodeGraphBuilder)([
        scannedNode(fqcn: 'App\\Monitor', alias: 'monitor', axes: [
            'layer' => ['model'],
            'context' => ['monitor', 'incident'],
        ]),
    ]);

    expect($notes[0]->frontmatter)->toBe([
        'alias' => 'monitor',
        'class' => 'Monitor',
        'fqcn' => 'App\\Monitor',
        'source' => '/src/App/Monitor.php',
        'layer' => 'model',
        'context' => ['monitor', 'incident'],
    ]);
});

test('a source path inside the application root becomes relative', function () {
    $notes = (new NodeGraphBuilder)([
        new ScannedClass(
            fqcn: 'App\\Monitor',
            axes: [],
            references: [],
            sourcePath: base_path('src/App/Monitor.php'),
            alias: 'monitor',
        ),
    ]);

    expect($notes[0]->frontmatter['source'])->toBe('src/App/Monitor.php');
});

test('a source path inside the current working directory becomes relative', function () {
    $notes = (new NodeGraphBuilder)([
        new ScannedClass(
            fqcn: 'App\\Monitor',
            axes: [],
            references: [],
            sourcePath: getcwd().'/src/App/Monitor.php',
            alias: 'monitor',
        ),
    ]);

    expect($notes[0]->frontmatter['source'])->toBe('src/App/Monitor.php');
});

test('the docblock summary travels into the note as its description', function () {
    $notes = (new NodeGraphBuilder)([
        scannedNode(fqcn: 'App\\Monitor', alias: 'monitor', description: 'Watches endpoints for downtime.'),
        scannedNode(fqcn: 'App\\Team', alias: 'team'),
    ]);

    expect($notes[0]->description)->toBe('Watches endpoints for downtime.')
        ->and($notes[1]->description)->toBeNull();
});

test('the parent class lands in frontmatter only when the class extends something', function () {
    $notes = (new NodeGraphBuilder)([
        scannedNode(fqcn: 'App\\Monitor', alias: 'monitor', extendsFqcn: Model::class),
        scannedNode(fqcn: 'App\\Team', alias: 'team'),
    ]);

    expect($notes[0]->frontmatter['extends'])->toBe(Model::class)
        ->and($notes[1]->frontmatter)->not->toHaveKey('extends');
});

test('a custom axis colliding with a reserved identity key fails loudly', function (string $reservedAxisKey) {
    expect(fn () => (new NodeGraphBuilder)([
        scannedNode(fqcn: 'App\\Monitor', alias: 'monitor', axes: [$reservedAxisKey => ['boom']]),
    ]))->toThrow(ReservedAxisKeyException::class, $reservedAxisKey);
})->with(['source', 'class', 'fqcn']);

test('two classes claiming the same alias fail loudly', function () {
    expect(fn () => (new NodeGraphBuilder)([
        scannedNode(fqcn: 'App\\First', alias: 'shared-alias'),
        scannedNode(fqcn: 'App\\Second', alias: 'shared-alias'),
    ]))->toThrow(AliasCollisionException::class, 'shared-alias');
});

test('duplicate links to the same node collapse into one wikilink', function () {
    $notes = (new NodeGraphBuilder)([
        scannedNode(fqcn: 'App\\Monitor', alias: 'monitor', references: ['App\\Team', '\\App\\Team']),
        scannedNode(fqcn: 'App\\Team', alias: 'team'),
    ]);

    expect($notes[0]->links)->toBe(['team']);
});
