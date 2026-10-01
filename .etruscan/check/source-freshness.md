---
alias: source-freshness
class: SourceFreshness
fqcn: WellDigit\Etruscan\Services\SourceFreshness
source: src/Services/SourceFreshness.php
layer: service
context: check
generated_by: etruscan
---

## Description

Compares the local index with current scanned PHP contents, roots, marker settings, and generated
note structure. Reports current, stale, incomplete, or unknown without rewriting files or claiming
to verify human prose or runtime wiring.

## References

- [[etruscan-config]]
- [[reference-index]]
- [[source-inventory]]

## Referenced by

- [[map-reader]]
