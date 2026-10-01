---
alias: reserved-axis-key
class: ReservedAxisKeyException
fqcn: WellDigit\Etruscan\Exceptions\ReservedAxisKeyException
extends: RuntimeException
source: src/Exceptions/ReservedAxisKeyException.php
layer: exception
context: taxonomy
generated_by: etruscan
---

## Description

Thrown when a custom axis derives a frontmatter key that collides with an identity key. A colliding
axis would silently overwrite identity metadata, so the projection fails loud instead.

## Referenced by

- [[etruscan-generate]]
- [[etruscan-graph]]
- [[node-graph-builder]]
