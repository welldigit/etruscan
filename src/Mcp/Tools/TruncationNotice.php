<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Tools;

/**
 * The one sentence every tool uses to say it held something back. Silent
 * truncation is worse than a long reply: the reader cannot tell a complete
 * answer from a clipped one, so every cut states what was kept, what exists,
 * and the exact call that fetches the rest.
 *
 * Deliberately in one place and deliberately uniform — a reader learns the
 * shape once and recognises it from any tool, which is the same reason
 * NoteTrustReminder has a single owner.
 */
#[\EtruscanNode('truncation-notice')]
#[\EtruscanLayer('utility')]
#[\EtruscanContext('mcp')]
final class TruncationNotice
{
    /**
     * @param  string  $what  What was counted, plural — "neighbour descriptions".
     * @param  int  $shown  How many made it into the reply.
     * @param  int  $total  How many exist.
     * @param  string  $retrieval  How to get the rest, without trailing punctuation.
     */
    public static function line(string $what, int $shown, int $total, string $retrieval): string
    {
        return sprintf('Showing %d of %d %s — %s.', $shown, $total, $what, $retrieval);
    }
}
