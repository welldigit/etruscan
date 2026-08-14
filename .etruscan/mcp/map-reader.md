---
alias: map-reader
class: MapReader
fqcn: WellDigit\Etruscan\Mcp\Services\MapReader
source: src/Mcp/Services/MapReader.php
layer: service
context: mcp
generated_by: etruscan
---

## Description

The opening move every map tool makes: read the configured vault. Injected rather than mixed in, so
the four tools take one dependency instead of wiring the reader and the config themselves, and so
the sentence an agent gets when there is no map yet — its only instruction about what to do next
— is written once, as `EMPTY_MAP_MESSAGE`.

## References

- [[etruscan-config]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[parsed-note]]
- [[vault-reader]]

## Referenced by

- [[lookup-node]]
- [[map-overview]]
- [[search-map]]
- [[trace-node]]
