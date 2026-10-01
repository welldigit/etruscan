---
alias: alias-collision
class: AliasCollisionException
fqcn: WellDigit\Etruscan\Exceptions\AliasCollisionException
extends: RuntimeException
source: src/Exceptions/AliasCollisionException.php
layer: exception
context: projection
generated_by: etruscan
---

## Description

Thrown when two classes claim the same node alias. A vault cannot hold two notes with one filename,
so the projection fails loud rather than clobbering.

## Referenced by

- [[etruscan-generate]]
- [[etruscan-graph]]
- [[node-graph-builder]]
