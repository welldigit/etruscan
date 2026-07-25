<?php

declare(strict_types=1);

use WellDigit\Etruscan\Enums\GeneratedNoteSection;

test('a section heading is the markdown h2 of its title', function (GeneratedNoteSection $generatedNoteSection, string $expectedHeading) {
    expect($generatedNoteSection->heading())->toBe($expectedHeading);
})->with([
    'description' => [GeneratedNoteSection::Description, '## Description'],
    'references' => [GeneratedNoteSection::References, '## References'],
]);
