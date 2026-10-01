---
alias: node-summary-line
class: NodeSummaryLine
fqcn: WellDigit\Etruscan\Utilities\NodeSummaryLine
source: src/Utilities/NodeSummaryLine.php
layer: utility
context:
  - mcp
  - export
generated_by: etruscan
---

## Description

One node on one line: what it is called, where it lives, what it is for. Rendered identically by
search results and by the exported index, because a reader who meets the same node twice described
two different ways has to work out whether they are the same node. The description is collapsed and
clipped — the line's job is to get a reader to the right file, not to carry the whole
specification.

## References

- [[identity-frontmatter-key]]
- [[parsed-note]]
- [[text-clipper]]

## Referenced by

- [[map-digest-renderer]]
- [[search-map]]
