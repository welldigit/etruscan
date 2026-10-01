<?php

declare(strict_types=1);

use WellDigit\Etruscan\Utilities\TextClipper;

test('text inside the budget is returned untouched', function () {
    expect(TextClipper::clip('short', 140))->toBe('short')
        ->and(TextClipper::clip(str_repeat('a', 140), 140))->toBe(str_repeat('a', 140));
});

test('the ellipsis is spent from inside the budget, so the limit is a true ceiling', function () {
    $clipped = TextClipper::clip(str_repeat('a', 300), 140);

    expect(mb_strlen($clipped))->toBe(140)
        ->and($clipped)->toEndWith('...');
});

/**
 * The regression this class exists for. A byte-based cut through a multibyte
 * character yields invalid UTF-8; json_encode() then returns false, and
 * laravel/mcp's `json_encode(...) ?: ''` turns that into a zero-length
 * JSON-RPC frame — the tool call returns nothing at all, and raises nothing.
 *
 * Asserted across every offset in the cut window, because the old behaviour
 * depended entirely on where the character happened to land. A length
 * assertion would not have caught it.
 */
test('a clip landing inside a multibyte character still encodes', function () {
    foreach (range(125, 145) as $offset) {
        $text = str_repeat('a', $offset).'—'.str_repeat('b', 60);
        $clipped = TextClipper::clip($text, 140);

        expect(mb_check_encoding($clipped, 'UTF-8'))->toBeTrue("em dash at byte {$offset}")
            ->and(json_encode(['text' => $clipped], JSON_UNESCAPED_UNICODE))->not->toBeFalse("em dash at byte {$offset}")
            ->and(mb_strlen($clipped))->toBeLessThanOrEqual(140, "em dash at byte {$offset}");
    }
});

test('the budget counts characters, not bytes, so em dashes do not shorten the clip', function () {
    $text = str_repeat('—', 300);

    expect(mb_strlen(TextClipper::clip($text, 140)))->toBe(140);
});

test('a line-boundary clip backs up to the last break rather than cutting mid-line', function () {
    $text = "first line\nsecond line\n".str_repeat('x', 200);
    $clipped = TextClipper::clipAtLineBoundary($text, 40);

    expect($clipped)->toBe("first line\nsecond line\n...")
        ->and(mb_strlen($clipped))->toBeLessThanOrEqual(40);
});

test('a line-boundary clip falls back to a hard clip when the budget holds no break', function () {
    $clipped = TextClipper::clipAtLineBoundary(str_repeat('x', 200), 40);

    expect(mb_strlen($clipped))->toBe(40)
        ->and($clipped)->toEndWith('...');
});

test('a budget too small for the ellipsis still returns valid text', function () {
    expect(mb_strlen(TextClipper::clip(str_repeat('a', 50), 2)))->toBe(2)
        ->and(TextClipper::clip(str_repeat('a', 50), 0))->toBe('');
});
