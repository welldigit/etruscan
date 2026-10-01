---
alias: map-overview-builder
class: MapOverviewBuilder
fqcn: WellDigit\Etruscan\Mcp\Services\MapOverviewBuilder
source: src/Mcp/Services/MapOverviewBuilder.php
layer: service
context: mcp
generated_by: etruscan
---

## Description

The grouping behind [[map-overview]]: node aliases bucketed by one axis's values, multi-value axes
landing in every group, axis-less nodes under (none). Falls back to the first real axis when the
requested one appears nowhere — identity keys and the generation marker never count as axes.

## References

- [[identity-frontmatter-key]]
- [[parsed-note]]

## Referenced by

- [[map-digest-renderer]]
- [[map-overview]]
