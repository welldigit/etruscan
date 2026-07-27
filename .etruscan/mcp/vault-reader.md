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

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[note-parser]]
- [[parsed-note]]

## Referenced by

- [[lookup-node]]
- [[map-overview]]
- [[search-map]]
- [[trace-node]]
