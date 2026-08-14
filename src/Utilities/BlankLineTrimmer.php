<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

/**
 * Trims a block down to its content lines without touching the indentation of
 * the first one. Plain trim() eats leading spaces along with leading newlines,
 * which silently demotes the opening line of an indented code block to prose
 * and leaves the rest of the block indented — so descriptions round-trip
 * through the vault byte-for-byte only if the edges are trimmed by line.
 */
#[EtruscanNode('blank-line-trimmer')]
#[EtruscanLayer('utility')]
#[EtruscanContext('projection')]
#[EtruscanContext('vault')]
final class BlankLineTrimmer
{
    public static function trim(string $text): string
    {
        $lines = preg_split('/\R/', $text) ?: [];

        while ($lines !== [] && trim($lines[0]) === '') {
            array_shift($lines);
        }

        while ($lines !== [] && trim((string) end($lines)) === '') {
            array_pop($lines);
        }

        return implode("\n", $lines);
    }
}
