---
alias: broken-link-checker
class: BrokenLinkChecker
fqcn: WellDigit\Etruscan\Services\BrokenLinkChecker
source: src/Services/BrokenLinkChecker.php
layer: service
context: check
generated_by: etruscan
---

## Description

Scans every vault note for wikilinks whose target is not a known node alias. Generated links always
resolve, so a hit is a dangling link in someone's manual notes — typically left behind when an
alias was renamed or a node removed.

## References

- [[check-category]]
- [[check-finding]]
- [[check-severity]]

## Referenced by

- [[etruscan-check]]
