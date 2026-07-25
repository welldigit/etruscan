<?php

declare(strict_types=1);

use WellDigit\Etruscan\Payloads\NoteContent;
use WellDigit\Etruscan\Services\GraphPageRenderer;

test('the graph page embeds nodes and edges as JSON', function () {
    $html = (new GraphPageRenderer)([
        new NoteContent(
            alias: 'monitor-create',
            frontmatter: [
                'alias' => 'monitor-create',
                'class' => 'MonitorCreate',
                'fqcn' => 'App\\MonitorCreate',
                'source' => 'src/App/MonitorCreate.php',
                'layer' => 'action',
            ],
            links: ['monitor'],
            description: 'Creates a monitor.',
        ),
        new NoteContent(
            alias: 'monitor',
            frontmatter: ['alias' => 'monitor', 'class' => 'Monitor', 'fqcn' => 'App\\Monitor', 'source' => 'src/App/Monitor.php'],
            links: [],
        ),
    ]);

    expect($html)->toContain('"id":"monitor-create"')
        ->toContain('"source":"monitor-create","target":"monitor"')
        ->toContain('"layer":"action"')
        ->toContain('"description":"Creates a monitor."')
        ->toContain('<canvas');
});

test('identity keys never leak into the axes object', function () {
    $html = (new GraphPageRenderer)([
        new NoteContent(
            alias: 'monitor',
            frontmatter: ['alias' => 'monitor', 'class' => 'Monitor', 'fqcn' => 'App\\Monitor', 'source' => 'src/App/Monitor.php', 'domain' => 'watch'],
            links: [],
        ),
    ]);

    expect($html)->toContain('"axes":{"domain":"watch"}');
});

test('script-breaking characters in descriptions are escaped', function () {
    $html = (new GraphPageRenderer)([
        new NoteContent(
            alias: 'sneaky',
            frontmatter: ['alias' => 'sneaky'],
            links: [],
            description: 'x</script>y',
        ),
    ]);

    expect($html)->not->toContain('x</script>y')
        ->and($html)->toContain('x\\u003C/script\\u003Ey');
});

test('rendering the same notes twice is deterministic', function () {
    $notes = [new NoteContent(alias: 'monitor', frontmatter: ['alias' => 'monitor'], links: [])];
    $graphPageRenderer = new GraphPageRenderer;

    expect($graphPageRenderer($notes))->toBe($graphPageRenderer($notes));
});
