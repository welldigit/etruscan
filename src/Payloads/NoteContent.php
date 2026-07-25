<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Payloads;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('note-content')]
#[EtruscanLayer('payload')]
#[EtruscanContext('projection')]
final readonly class NoteContent
{
    /**
     * @param  string  $alias  Note filename stem and wikilink target.
     * @param  array<string, string|list<string>>  $frontmatter  YAML properties.
     * @param  list<string>  $links  Aliases of nodes this one references (outbound).
     * @param  list<string>  $referencedBy  Aliases of nodes that reference this one (inbound).
     * @param  string|null  $description  Rendered as the Description section, if any.
     */
    public function __construct(
        public string $alias,
        public array $frontmatter,
        public array $links,
        public array $referencedBy = [],
        public ?string $description = null,
    ) {}
}
