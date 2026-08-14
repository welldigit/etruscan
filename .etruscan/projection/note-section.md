---
alias: note-section
class: NoteSection
fqcn: WellDigit\Etruscan\Enums\NoteSection
source: src/Enums/NoteSection.php
layer: enum
context: projection
generated_by: etruscan
---

## Description

The structured markdown sections of a note (Description, References, Referenced by) — shared by
the renderer that writes them and the parser that carves them out of an existing note, so a rename
cannot desync the two. References and Referenced by are derived and rewritten each run; Description
is the human's and is only ever carried.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]

## Referenced by

- [[markdown-note-renderer]]
- [[note-parser]]
