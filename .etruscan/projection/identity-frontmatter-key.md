---
alias: identity-frontmatter-key
class: IdentityFrontmatterKey
fqcn: WellDigit\Etruscan\Enums\IdentityFrontmatterKey
source: src/Enums/IdentityFrontmatterKey.php
layer: enum
context: projection
generated_by: etruscan
---

## Description

Single source of truth for the identity keys stamped on every note (alias, class, fqcn, extends,
source). Both the frontmatter generation and the reserved-key guard read from it, so they can never
drift apart.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]

## Referenced by

- [[graph-page-renderer]]
- [[lookup-node]]
- [[map-overview-builder]]
- [[map-search]]
- [[node-graph-builder]]
- [[note-parser]]
- [[search-map]]
