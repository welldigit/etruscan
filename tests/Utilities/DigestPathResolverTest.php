<?php

declare(strict_types=1);

use WellDigit\Etruscan\Utilities\DigestPathResolver;

// Deliberately not inside .reports/ — that folder seeds a .gitignore, and the
// index is the one artifact a consumer is meant to commit.
test('the index sits at the vault root, not among the machine-local reports', function () {
    expect(DigestPathResolver::resolve('/app/.etruscan'))
        ->toBe('/app/.etruscan'.DIRECTORY_SEPARATOR.'map.md')
        ->and(DigestPathResolver::resolve('/app/.etruscan'))->not->toContain('.reports');
});
