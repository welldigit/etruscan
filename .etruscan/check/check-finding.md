---
alias: check-finding
class: CheckFinding
fqcn: WellDigit\Etruscan\Payloads\CheckFinding
source: src/Payloads/CheckFinding.php
layer: payload
context: check
generated_by: etruscan
---

## Description

One problem the checkers found: a category, a severity (error or warning), and a human-readable
message. The command groups these for output and derives its exit code from them.

## References

- [[check-category]]
- [[check-severity]]

## Referenced by

- [[broken-link-checker]]
- [[digest-staleness-checker]]
- [[duplicate-alias-checker]]
- [[etruscan-check]]
- [[structural-map-checker]]
- [[unresolved-attribute-checker]]
- [[vocabulary-checker]]
