---
alias: note-content
class: NoteContent
fqcn: WellDigit\Etruscan\Payloads\NoteContent
source: src/Payloads/NoteContent.php
layer: payload
context: projection
generated_by: etruscan
---

## Description

A fully resolved node, ready to render as one markdown note: alias, frontmatter, and both edge
lists. The optional description is only ever the note's own human text (read from the vault for
graph rendering) — generation never derives one.

## Referenced by

- [[markdown-note-renderer]]
- [[node-graph-builder]]
- [[note-path-resolver]]
