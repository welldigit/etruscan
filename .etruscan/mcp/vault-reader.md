---
alias: vault-reader
class: VaultReader
fqcn: WellDigit\Etruscan\Services\VaultReader
source: src/Services/VaultReader.php
layer: service
context:
  - vault
  - mcp
generated_by: etruscan
---

## Description

Reads the whole vault from disk into [[parsed-note]] payloads keyed by alias, generated notes only.
The read-side counterpart of [[vault-writer]]: both speak through [[note-parser]], so what was
written is exactly what is read.

## References

- [[note-parser]]

## Referenced by

- [[digest-staleness-checker]]
- [[etruscan-export]]
- [[etruscan-generate]]
- [[etruscan-usage]]
- [[map-reader]]
- [[reference-index]]
- [[structural-map-checker]]
- [[vault-description-reader]]
