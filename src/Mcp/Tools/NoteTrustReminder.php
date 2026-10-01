<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Tools;

/**
 * The one line every note-serving tool appends: a cue, at the point of use, to
 * check an enforcement claim against the code before repeating it. Deliberately
 * in one place — lookup-node and trace-node must never drift into different
 * trust stories, and the wording is edited here or nowhere.
 *
 * It is a pointer, not a lesson. What a description can and cannot be trusted
 * for is stated once per session in the server instructions, the Boost
 * guideline and the navigate skill; repeating that definition on every note
 * served bought a second copy of a standing rule at a per-call price.
 */
#[\EtruscanNode('note-trust-reminder')]
#[\EtruscanLayer('utility')]
#[\EtruscanContext('mcp')]
final class NoteTrustReminder
{
    public const string LINE = 'Verify any enforcement claim above (validation, authorization, expiry) in the source before repeating it.';
}
