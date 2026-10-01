<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Services\ContextFootprintCalculator;

beforeEach(function () {
    $this->sourceDirectory = base_path('etruscan-footprint-src');
    File::ensureDirectoryExists($this->sourceDirectory);
    $this->notePath = base_path('etruscan-footprint-note.md');
});

afterEach(function () {
    File::deleteDirectory($this->sourceDirectory);
    File::delete($this->notePath);
});

function footprintNote(?string $notePath, ?string $source): ParsedNote
{
    return new ParsedNote(
        alias: 'alpha',
        frontmatter: $source === null ? ['alias' => 'alpha'] : ['alias' => 'alpha', 'source' => $source],
        description: '',
        manual: '',
        links: [],
        referencedBy: [],
        path: $notePath,
    );
}

test('the footprint weighs the notes against the source they stand in for', function () {
    File::put($this->notePath, str_repeat('n', 100));
    File::put($this->sourceDirectory.'/Alpha.php', str_repeat('s', 900));

    $footprint = app(ContextFootprintCalculator::class)([
        'alpha' => footprintNote($this->notePath, 'etruscan-footprint-src/Alpha.php'),
    ]);

    expect($footprint->notes)->toBe(1)
        ->and($footprint->noteChars)->toBe(100)
        ->and($footprint->sourceChars)->toBe(900)
        ->and($footprint->ratio())->toBe(9.0)
        ->and($footprint->sourcesMissing)->toBe(0);
});

// A stale path is a fact about the map worth reporting, not a reason to take
// the usage report down.
test('a note whose source has gone is counted, not fatal', function () {
    File::put($this->notePath, str_repeat('n', 100));

    $footprint = app(ContextFootprintCalculator::class)([
        'alpha' => footprintNote($this->notePath, 'etruscan-footprint-src/Gone.php'),
        'beta' => footprintNote($this->notePath, null),
    ]);

    expect($footprint->notes)->toBe(2)
        ->and($footprint->sourceChars)->toBe(0)
        ->and($footprint->sourcesMissing)->toBe(2);
});

test('an empty map has no footprint and no ratio to divide by', function () {
    $footprint = app(ContextFootprintCalculator::class)([]);

    expect($footprint->notes)->toBe(0)
        ->and($footprint->ratio())->toBe(0.0);
});

test('a note parsed from a string rather than read carries no size', function () {
    $footprint = app(ContextFootprintCalculator::class)([
        'alpha' => footprintNote(null, null),
    ]);

    expect($footprint->noteChars)->toBe(0);
});
