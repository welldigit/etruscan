---
alias: digest-staleness-checker
class: DigestStalenessChecker
fqcn: WellDigit\Etruscan\Services\DigestStalenessChecker
source: src/Services/DigestStalenessChecker.php
layer: service
context:
  - check
  - export
generated_by: etruscan
---

## Description

Compares the exported map with a fresh rendering of the vault, independent of filesystem timestamps.
A differing export is a warning promoted by strict checks; exporting remains optional. It does not
modify the index or human notes.

## References

- [[check-category]]
- [[check-finding]]
- [[check-severity]]
- [[digest-path-resolver]]
- [[etruscan-config]]
- [[map-digest-renderer]]
- [[vault-reader]]

## Referenced by

- [[etruscan-check]]
