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

Tells node and axis attributes apart: #[EtruscanNode] by exact FQCN, an axis as any EtruscanAxis subclass (probed once, cached). Derives the frontmatter key from the attribute's short name.

## References

- [[etruscan-axis]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
