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

Renders one note deterministically: YAML frontmatter carrying the generation marker, an optional
Description section (wrapped as readable prose), References as [[wikilinks]], and any carried-over
manual content below.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[generated-note-section]]
- [[note-content]]

## Referenced by

- [[vault-writer]]
