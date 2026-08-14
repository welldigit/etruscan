<?php

declare(strict_types=1);

use WellDigit\Etruscan\Utilities\ReportsPathResolver;

test('generated artifacts live under .reports inside the vault', function () {
    expect(ReportsPathResolver::resolve('/app/.etruscan', 'graph.html'))
        ->toBe('/app/.etruscan'.DIRECTORY_SEPARATOR.'.reports'.DIRECTORY_SEPARATOR.'graph.html');
});
