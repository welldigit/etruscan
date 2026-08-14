<?php

declare(strict_types=1);

use WellDigit\Etruscan\Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__);

/**
 * json_decode() hands back `mixed`, which every assertion then has to re-prove.
 * Decoding through one helper that asserts the shape keeps the tests readable
 * and lets static analysis see the array the test is actually talking about.
 *
 * @return array<string, mixed>
 */
function decodeJson(string $json): array
{
    $decoded = json_decode(trim($json), true);

    expect($decoded)->toBeArray();

    /** @var array<string, mixed> $decoded */
    return $decoded;
}
