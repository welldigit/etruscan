<?php

declare(strict_types=1);

use WellDigit\Etruscan\Enums\NoteSection;

test('a section heading is the markdown h2 of its title', function (NoteSection $noteSection, string $expectedHeading) {
    expect($noteSection->heading())->toBe($expectedHeading);
})->with([
    'description' => [NoteSection::Description, '## Description'],
    'references' => [NoteSection::References, '## References'],
]);
