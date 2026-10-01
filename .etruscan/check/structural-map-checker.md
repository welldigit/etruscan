---
alias: structural-map-checker
class: StructuralMapChecker
fqcn: WellDigit\Etruscan\Services\StructuralMapChecker
source: src/Services/StructuralMapChecker.php
layer: service
context: check
generated_by: etruscan
---

## Description

Compares freshly derived node metadata and both link directions against the vault without writing.
Missing or changed generated structure is an error; retained notes with no annotated class are
warnings. It needs no local index, so CI can check committed notes directly.

## References

- [[check-category]]
- [[check-finding]]
- [[check-severity]]
- [[etruscan-config]]
- [[vault-reader]]

## Referenced by

- [[etruscan-check]]
