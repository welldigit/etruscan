---
alias: duplicate-alias-checker
class: DuplicateAliasChecker
fqcn: WellDigit\Etruscan\Services\DuplicateAliasChecker
source: src/Services/DuplicateAliasChecker.php
layer: service
context: check
generated_by: etruscan
---

## Description

Groups scanned nodes by alias and reports any alias claimed by more than one class — the collision
that would otherwise fail generation loud, surfaced proactively as an error.

## References

- [[check-category]]
- [[check-finding]]
- [[check-severity]]

## Referenced by

- [[etruscan-check]]
