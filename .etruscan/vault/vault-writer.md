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

Owns the write-and-sweep cycle: rewrites generated notes, carries manual content across
regenerations (and across folder moves), removes stale copies, keeps orphans that hold manual notes
unless purging, and prunes emptied folders. Files without the generation marker are never touched.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[generated-note-section]]
- [[identity-frontmatter-key]]
- [[markdown-note-renderer]]
- [[note-content]]
- [[note-path-resolver]]

## Referenced by

- [[etruscan-generate]]
