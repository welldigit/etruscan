---
alias: vault-writer
class: VaultWriter
fqcn: WellDigit\Etruscan\Services\VaultWriter
source: src/Services/VaultWriter.php
layer: service
context: vault
generated_by: etruscan
---

## Description

Owns the write-and-sweep cycle: rewrites generated notes, carries the human description and manual
content across regenerations (and across folder moves) keyed by alias, removes stale copies, keeps
orphans that hold human words unless purging, and prunes emptied folders. Files without the
generation marker are never touched.

## References

- [[markdown-note-renderer]]
- [[note-parser]]
- [[note-path-resolver]]

## Referenced by

- [[etruscan-generate]]
