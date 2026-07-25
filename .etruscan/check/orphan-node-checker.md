---
alias: orphan-node-checker
class: OrphanNodeChecker
fqcn: WellDigit\Etruscan\Services\OrphanNodeChecker
source: src/Services/OrphanNodeChecker.php
layer: service
context: check
generated_by: etruscan
---

## Description

Flags nodes with neither inbound nor outbound links — isolated islands that usually mean a missed
annotation or a mis-scoped node. Reads [[note-content]]'s links and referencedBy directly, so it
never recomputes the graph.

## References

- [[check-category]]
- [[check-finding]]
- [[check-severity]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[note-content]]

## Referenced by

- [[etruscan-check]]
