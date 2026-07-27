---
alias: note-parser
class: NoteParser
fqcn: WellDigit\Etruscan\Services\NoteParser
source: src/Services/NoteParser.php
layer: service
context: vault
generated_by: etruscan
---

## Description

Single owner of note-file parsing: frontmatter (scalars and lists, quoting rules), the Description
body, References and Referenced-by edges, and the human content outside all generated sections.
[[vault-writer]] carves with it and [[vault-reader]] reads with it, so the two can never disagree
about what a note contains.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[generated-note-section]]
- [[identity-frontmatter-key]]
- [[parsed-note]]

## Referenced by

- [[vault-reader]]
- [[vault-writer]]
