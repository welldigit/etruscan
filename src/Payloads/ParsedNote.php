<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Payloads;

#[\EtruscanNode('parsed-note')]
#[\EtruscanLayer('payload')]
#[\EtruscanContext('vault')]
final readonly class ParsedNote
{
    /**
     * @param  string|null  $alias  Alias from the frontmatter, or null when absent.
     * @param  array<string, string|list<string>>  $frontmatter  All frontmatter properties.
     * @param  string  $description  Body of the Description section.
     * @param  string  $manual  Human content outside the generated sections.
     * @param  list<string>  $links  Aliases listed under References.
     * @param  list<string>  $referencedBy  Aliases listed under Referenced by.
     * @param  string|null  $path  Where the note lives, app-relative; null when parsed from a string rather than read from the vault.
     */
    public function __construct(
        public ?string $alias,
        public array $frontmatter,
        public string $description,
        public string $manual,
        public array $links,
        public array $referencedBy,
        public ?string $path = null,
    ) {}
}
