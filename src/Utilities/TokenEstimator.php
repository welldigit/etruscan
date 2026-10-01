<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

/**
 * Turns a served character count into the rough token count people actually
 * reason about. Deliberately crude and deliberately in one place: the ratio is
 * a rule of thumb, not a tokenizer, and every surface that reports it — the
 * text report, the JSON contract, the dashboard — must quote the same number.
 *
 * The default of four characters per token is the familiar figure for English
 * prose, and map payloads are not English prose — aliases, FQCNs and paths all
 * tokenize worse than that — so the default reads optimistically for exactly
 * the content this package serves. Real counts are model-specific and only a
 * tokenizer can produce them, which is why the ratio is configurable
 * (`etruscan.chars_per_token`) with the calibration recipe in the config file,
 * and why every surface calls the figure an estimate rather than a count.
 */
#[\EtruscanNode('token-estimator')]
#[\EtruscanLayer('utility')]
#[\EtruscanContext('usage')]
final class TokenEstimator
{
    public const int DEFAULT_CHARS_PER_TOKEN = 4;

    public static function estimate(int $chars): int
    {
        return (int) round($chars / EtruscanConfig::charsPerToken());
    }
}
