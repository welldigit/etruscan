<?php

declare(strict_types=1);

use WellDigit\Etruscan\Utilities\TokenEstimator;

test('characters are converted at the documented ratio', function () {
    expect(TokenEstimator::estimate(400))->toBe(100)
        ->and(TokenEstimator::CHARS_PER_TOKEN)->toBe(4);
});

test('the estimate rounds rather than truncates', function () {
    expect(TokenEstimator::estimate(6))->toBe(2)
        ->and(TokenEstimator::estimate(5))->toBe(1);
});

test('nothing served estimates as no tokens', function () {
    expect(TokenEstimator::estimate(0))->toBe(0);
});
