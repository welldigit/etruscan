---
alias: source-reference-trace
class: SourceReferenceTrace
fqcn: WellDigit\Etruscan\Mcp\Services\SourceReferenceTrace
source: src/Mcp/Services/SourceReferenceTrace.php
layer: service
context: mcp
generated_by: etruscan
---

## Description

Serves a bounded page of static usage evidence from the local index, including unannotated callers.
Reports occurrence totals and the next offset; withholds evidence unless the supplied freshness
status is current. Every page states the unresolved scope.

## References

- [[etruscan-config]]
- [[reference-index]]
- [[trace-direction]]

## Referenced by

- [[trace-node]]
