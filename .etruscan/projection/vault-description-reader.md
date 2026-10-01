---
alias: vault-description-reader
class: VaultDescriptionReader
fqcn: WellDigit\Etruscan\Services\VaultDescriptionReader
source: src/Services/VaultDescriptionReader.php
layer: service
context:
  - vault
  - projection
generated_by: etruscan
---

## Description

Reads the human-written descriptions back out of the vault, keyed by alias. Since the scanner
stopped harvesting docblocks, the notes are a description's only home — so anything that wants to
show one, the graph page first among them, comes here rather than back to the code.

## References

- [[vault-reader]]

## Referenced by

- [[etruscan-graph]]
