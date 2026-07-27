<?php

declare(strict_types=1);

use WellDigit\Etruscan\Services\NoteParser;

test('a full generated note parses into all its parts', function () {
    $parsedNote = (new NoteParser)(implode("\n", [
        '---',
        'alias: monitor-create',
        'class: MonitorCreate',
        'fqcn: "Core\\\\Domain\\\\MonitorCreate"',
        'source: src/MonitorCreate.php',
        'layer: action',
        'context:',
        '  - monitor',
        '  - billing',
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
        '- [[monitor-data]]',
        '',
        '## Referenced by',
        '',
        '- [[monitor-controller]]',
        '',
        'A manual note below everything.',
    ])."\n");

    expect($parsedNote->alias)->toBe('monitor-create')
        ->and($parsedNote->frontmatter['class'])->toBe('MonitorCreate')
        ->and($parsedNote->frontmatter['fqcn'])->toBe('Core\\Domain\\MonitorCreate')
        ->and($parsedNote->frontmatter['layer'])->toBe('action')
        ->and($parsedNote->frontmatter['context'])->toBe(['monitor', 'billing'])
        ->and($parsedNote->frontmatter)->toHaveKey('generated_by')
        ->and($parsedNote->description)->toBe('Creates a monitor after guarding the plan cap.')
        ->and($parsedNote->links)->toBe(['monitor', 'monitor-data'])
        ->and($parsedNote->referencedBy)->toBe(['monitor-controller'])
        ->and($parsedNote->manual)->toBe('A manual note below everything.');
});

test('a hand-written note without frontmatter is all manual', function () {
    $parsedNote = (new NoteParser)("# My own note\n\nJust prose.\n");

    expect($parsedNote->alias)->toBeNull()
        ->and($parsedNote->frontmatter)->toBe([])
        ->and($parsedNote->description)->toBe('')
        ->and($parsedNote->links)->toBe([])
        ->and($parsedNote->manual)->toBe("# My own note\n\nJust prose.");
});

test('a quoted alias is unquoted with escapes resolved', function () {
    $parsedNote = (new NoteParser)("---\nalias: \"we\\\"ird\"\n---\n\nBody.\n");

    expect($parsedNote->alias)->toBe('we"ird');
});

test('a multi-line description is captured until the next section', function () {
    $parsedNote = (new NoteParser)(implode("\n", [
        '---',
        'alias: x',
        '---',
        '',
        '## Description',
        '',
        'First line of prose',
        'second line of prose.',
        '',
        '## References',
        '',
        '- [[y]]',
    ])."\n");

    expect($parsedNote->description)->toBe("First line of prose\nsecond line of prose.")
        ->and($parsedNote->links)->toBe(['y']);
});

test('unclosed frontmatter is treated as frontmatter with an empty body', function () {
    $parsedNote = (new NoteParser)("---\nalias: broken\ngenerated_by: etruscan");

    expect($parsedNote->alias)->toBe('broken')
        ->and($parsedNote->frontmatter)->toHaveKey('generated_by')
        ->and($parsedNote->manual)->toBe('');
});
