---
alias: markdown-note-renderer
class: MarkdownNoteRenderer
fqcn: WellDigit\Etruscan\Services\MarkdownNoteRenderer
source: src/Services/MarkdownNoteRenderer.php
layer: service
context: projection
generated_by: etruscan
---

## Description

Renders one note deterministically: YAML frontmatter carrying the generation marker, the Description
section (heading always seeded — empty until a human fills it; the text is only ever carried,
never derived from code — prose is tidy-wrapped, text with its own formatting passes
byte-for-byte), References as wikilinks, and any carried-over manual content below.

## References

- [[blank-line-trimmer]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[note-content]]
- [[note-section]]

## Referenced by

- [[vault-writer]]
