---
alias: map-digest-renderer
class: MapDigestRenderer
fqcn: WellDigit\Etruscan\Services\MapDigestRenderer
source: src/Services/MapDigestRenderer.php
layer: service
context: export
generated_by: etruscan
---

## Description

Renders every node as one line — alias, source path, clipped intent — grouped by axis, with a
header telling the reader what the index is and what it is not. Deliberately free of timestamps and
anything else that moves on its own: the index is committed and imported into a cached prefix, so
identical input must produce identical bytes or every export dirties a diff and invalidates the
cache it exists to fill.

## References

- [[map-overview-builder]]
- [[node-summary-line]]

## Referenced by

- [[digest-staleness-checker]]
- [[etruscan-export]]
- [[etruscan-generate]]
