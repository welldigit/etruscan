<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Tools;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

/**
 * The one line every note-serving tool appends: what a description can be
 * trusted for, and what must be verified in the source. Deliberately in one
 * place — lookup-node and trace-node must never drift into different trust
 * stories, and the wording is edited here or nowhere.
 */
#[EtruscanNode('note-trust-reminder')]
#[EtruscanLayer('utility')]
#[EtruscanContext('mcp')]
final class NoteTrustReminder
{
    public const string LINE = 'Trust protocol: the description is testimony — authoritative for intent and rationale; verify enforcement claims (validation, authorization, expiry) in the source before repeating them.';
}
