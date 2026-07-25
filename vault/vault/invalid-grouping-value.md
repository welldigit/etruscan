---
alias: invalid-grouping-value
class: InvalidGroupingValueException
fqcn: WellDigit\Etruscan\Exceptions\InvalidGroupingValueException
extends: RuntimeException
source: src/Exceptions/InvalidGroupingValueException.php
layer: exception
context: vault
generated_by: etruscan
---

## Description

Thrown when a grouping axis value cannot be sanitized into a directory name — that is an annotation bug, so the projection fails loud rather than dumping the note somewhere surprising.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
