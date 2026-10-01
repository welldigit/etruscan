<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

use WellDigit\Etruscan\Enums\IdentityFrontmatterKey;
use WellDigit\Etruscan\Payloads\ParsedNote;

/**
 * One node on one line: what it is called, where it lives, and what it is for.
 *
 * Two surfaces render this line — search results and the exported digest that
 * sits in an agent's cached prefix — and they must render it identically. A
 * reader who meets the same node twice, described two different ways, has to
 * work out whether they are the same node; that is the drift this class exists
 * to prevent, for the same reason the trust reminder has a single owner.
 *
 * The description is collapsed to one line and clipped, because the line's job
 * is to get a reader to the right file, not to carry the whole specification.
 */
#[\EtruscanNode('node-summary-line')]
#[\EtruscanLayer('utility')]
#[\EtruscanContext('mcp')]
#[\EtruscanContext('export')]
final class NodeSummaryLine
{
    public const int DESCRIPTION_LIMIT = 140;

    public static function render(ParsedNote $parsedNote): string
    {
        $description = TextClipper::clip(
            (string) preg_replace('/\s+/', ' ', $parsedNote->description),
            self::DESCRIPTION_LIMIT,
        );

        $source = $parsedNote->frontmatter[IdentityFrontmatterKey::Source->value] ?? null;

        return sprintf(
            '- %s%s%s',
            $parsedNote->alias,
            is_string($source) ? ' ('.$source.')' : '',
            $description === '' ? '' : ' — '.$description,
        );
    }
}
