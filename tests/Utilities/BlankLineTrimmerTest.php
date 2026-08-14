<?php

declare(strict_types=1);

use WellDigit\Etruscan\Utilities\BlankLineTrimmer;

test('leading and trailing blank lines are removed', function () {
    expect(BlankLineTrimmer::trim("\n\n  \nBody.\n\n  \n"))->toBe('Body.');
});

test('the indentation of the first content line is left alone', function () {
    expect(BlankLineTrimmer::trim("\n    indented\n        deeper\n"))->toBe("    indented\n        deeper");
});

test('a blank-only block trims to nothing', function () {
    expect(BlankLineTrimmer::trim("   \n\n\t\n"))->toBe('')
        ->and(BlankLineTrimmer::trim(''))->toBe('');
});

test('interior blank lines survive', function () {
    expect(BlankLineTrimmer::trim("First.\n\nSecond."))->toBe("First.\n\nSecond.");
});

test('carriage returns are normalised to newlines', function () {
    expect(BlankLineTrimmer::trim("\r\n  kept\r\nalso kept\r\n"))->toBe("  kept\nalso kept");
});
