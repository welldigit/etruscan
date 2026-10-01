<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

/**
 * Clips served text to a character budget. Multibyte throughout, and
 * deliberately in one place: a byte-based cut through an em dash produces
 * invalid UTF-8, json_encode() then returns false, and laravel/mcp turns that
 * into a zero-length JSON-RPC frame — the tool call returns nothing at all,
 * with no error anywhere. Every clip on the serving path goes through here.
 *
 * The ellipsis is spent from inside the budget, so the limit is a true ceiling
 * rather than a ceiling plus three.
 */
#[\EtruscanNode('text-clipper')]
#[\EtruscanLayer('utility')]
#[\EtruscanContext('mcp')]
final class TextClipper
{
    public const string ELLIPSIS = '...';

    /**
     * Hard clip to $limit characters, ellipsis included.
     */
    public static function clip(string $text, int $limit): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $keep = $limit - mb_strlen(self::ELLIPSIS);

        return $keep < 1
            ? mb_substr($text, 0, max($limit, 0))
            : mb_substr($text, 0, $keep).self::ELLIPSIS;
    }

    /**
     * Clip to $limit characters, backing up to the last line break so a block
     * of human markdown is never cut mid-line. Falls back to a hard clip when
     * the budget holds no line break at all.
     */
    public static function clipAtLineBoundary(string $text, int $limit): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $head = mb_substr($text, 0, max($limit - mb_strlen(self::ELLIPSIS), 0));
        $lastBreak = mb_strrpos($head, "\n");

        if ($lastBreak === false) {
            return self::clip($text, $limit);
        }

        return rtrim(mb_substr($head, 0, $lastBreak))."\n".self::ELLIPSIS;
    }
}
