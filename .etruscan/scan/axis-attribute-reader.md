---
alias: axis-attribute-reader
class: AxisAttributeReader
fqcn: WellDigit\Etruscan\Services\AxisAttributeReader
source: src/Services/AxisAttributeReader.php
layer: service
context:
  - scan
  - taxonomy
generated_by: etruscan
---

## Description

Tells node and axis attributes apart: #[EtruscanNode] by name — the namespaced class or its global
twin — and an axis as any EtruscanAxis subclass, probed once through Composer autoloading and
cached. Derives the frontmatter key from the attribute's short name. Also spots what the zero-import
form invites: an Etruscan-named attribute that resolves to no class at all, because the leading
backslash or the import is missing.

## References

- [[etruscan-axis]]
- [[etruscan-node]]

## Referenced by

- [[codebase-scanner]]
- [[unresolved-attribute-checker]]
