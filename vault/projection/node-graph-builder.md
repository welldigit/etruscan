---
alias: node-graph-builder
class: NodeGraphBuilder
fqcn: WellDigit\Etruscan\Services\NodeGraphBuilder
source: src/Services/NodeGraphBuilder.php
layer: service
context: projection
generated_by: etruscan
---

## Description

Turns scanned classes into notes: builds the alias map (failing loud on collisions), resolves each node's code references into [[alias]] links, and assembles identity plus axis frontmatter. Source paths under the app root — or the working directory — become relative; non-node imports and self-links simply drop out.

## References

- [[alias-collision]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[identity-frontmatter-key]]
- [[note-content]]
- [[reserved-axis-key]]
- [[scanned-class]]
