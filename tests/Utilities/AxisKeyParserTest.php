<?php

declare(strict_types=1);

use WellDigit\Etruscan\Utilities\AxisKeyParser;

test('raw axis keys are trimmed, de-duplicated and stripped of empties', function (string $rawAxisKeys, array $expectedAxisKeys) {
    expect(AxisKeyParser::parse($rawAxisKeys))->toBe($expectedAxisKeys);
})->with([
    'single key' => ['layer', ['layer']],
    'list keeps nesting order' => ['layer,domain', ['layer', 'domain']],
    'whitespace is trimmed' => [' layer , domain ', ['layer', 'domain']],
    'empty segments are dropped' => [',layer,,', ['layer']],
    'duplicates collapse' => ['layer,layer,domain', ['layer', 'domain']],
    'nothing usable yields an empty list' => [' , ,', []],
]);
