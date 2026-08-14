<?php

declare(strict_types=1);

use WellDigit\Etruscan\Utilities\UsageLogPathResolver;

test('the usage log lives at .reports/usage.jsonl inside the vault', function () {
    expect(UsageLogPathResolver::resolve('/app/.etruscan'))->toBe('/app/.etruscan'.DIRECTORY_SEPARATOR.'.reports'.DIRECTORY_SEPARATOR.'usage.jsonl');
});
