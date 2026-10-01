---
alias: context-footprint-calculator
class: ContextFootprintCalculator
fqcn: WellDigit\Etruscan\Services\ContextFootprintCalculator
source: src/Services/ContextFootprintCalculator.php
layer: service
context: usage
generated_by: etruscan
---

## Description

Computes the counterfactual the usage log cannot see. The log records what the map delivered; it has
no way to record what reading the code instead would have cost, and that is the half every claim
about the map rests on. Walks the vault, resolves each note's `source` against the app root and
measures it on the filesystem — no model call, nothing estimated. An unreadable path is counted
rather than thrown, because a footprint is a diagnostic and a stale `source` should not take
[[etruscan-usage]] down with it.

## References

- [[absolute-path-resolver]]
- [[context-footprint]]
- [[identity-frontmatter-key]]

## Referenced by

- [[etruscan-usage]]
