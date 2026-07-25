---
alias: etruscan-axis
class: EtruscanAxis
fqcn: WellDigit\Etruscan\Attributes\EtruscanAxis
source: src/Attributes/EtruscanAxis.php
layer: attribute
context: taxonomy
generated_by: etruscan
---

## Description

Abstract base for grouping axes. Subclass it (`final readonly`, with its own `#[Attribute]` marker) to add a dimension — no registration needed. The frontmatter key is the short name, lowercased, minus the leading `Etruscan` (EtruscanTeam => team), and must not shadow an identity key.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
