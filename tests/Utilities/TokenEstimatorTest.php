<?php

declare(strict_types=1);

use WellDigit\Etruscan\Utilities\TokenEstimator;

test('characters are converted at the documented ratio', function () {
    expect(TokenEstimator::estimate(400))->toBe(100)
        ->and(TokenEstimator::DEFAULT_CHARS_PER_TOKEN)->toBe(4);
});

// The default is optimistic for alias- and FQCN-dense payloads, so a project
// that has actually counted its own can correct it without a code change.
test('a calibrated ratio overrides the default', function () {
    config()->set('etruscan.chars_per_token', 3);

    expect(TokenEstimator::estimate(300))->toBe(100);
});

test('a nonsense ratio falls back rather than dividing by zero', function () {
    config()->set('etruscan.chars_per_token', 0);
    expect(TokenEstimator::estimate(400))->toBe(400);

    config()->set('etruscan.chars_per_token', 'four');
    expect(TokenEstimator::estimate(400))->toBe(100);
});

test('the estimate rounds rather than truncates', function () {
    expect(TokenEstimator::estimate(6))->toBe(2)
        ->and(TokenEstimator::estimate(5))->toBe(1);
});

test('nothing served estimates as no tokens', function () {
    expect(TokenEstimator::estimate(0))->toBe(0);
});
