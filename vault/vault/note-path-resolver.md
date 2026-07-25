---
alias: note-path-resolver
class: NotePathResolver
fqcn: WellDigit\Etruscan\Services\NotePathResolver
source: src/Services/NotePathResolver.php
layer: service
context: vault
generated_by: etruscan
---

## Description

Decides where a note lives in the vault. Grouping axes nest folders in configured order; a level whose axis the note does not carry is skipped; multi-value axes group under the first value in sorted order. Axis values are slugged for the filesystem — a value that sanitizes to nothing fails loud.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[invalid-grouping-value]]
- [[note-content]]
