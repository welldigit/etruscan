<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

/**
 * Turns a served character count into the rough token count people actually
 * reason about. Deliberately crude and deliberately in one place: the ratio is
 * a rule of thumb, not a tokenizer, and every surface that reports it — the
 * text report, the JSON contract, the dashboard — must quote the same number.
 */
#[EtruscanNode('token-estimator')]
#[EtruscanLayer('utility')]
#[EtruscanContext('usage')]
final class TokenEstimator
{
    public const int CHARS_PER_TOKEN = 4;

    public static function estimate(int $chars): int
    {
        return (int) round($chars / self::CHARS_PER_TOKEN);
    }
}
