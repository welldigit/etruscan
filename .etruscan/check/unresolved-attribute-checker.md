---
alias: unresolved-attribute-checker
class: UnresolvedAttributeChecker
fqcn: WellDigit\Etruscan\Services\UnresolvedAttributeChecker
source: src/Services/UnresolvedAttributeChecker.php
layer: service
context: check
generated_by: etruscan
---

## Description

Reports an attribute named like Etruscan's that resolves to no class — what PHP does with
#[EtruscanNode] when the leading backslash or the import is missing: the name resolves inside the
annotated class's own namespace, PHP accepts it in silence, and the class never reaches the map.
Walks the attribute evidence of every scanned class, reports each class and attribute once with the
file and line, and names the fix. A warning, not an error: nothing already on the map is broken, a
class is merely absent from it — and --strict promotes it for CI.

## References

- [[axis-attribute-reader]]
- [[check-category]]
- [[check-finding]]
- [[check-severity]]

## Referenced by

- [[etruscan-check]]
- [[etruscan-generate]]
