---
alias: etruscan-config
class: EtruscanConfig
fqcn: WellDigit\Etruscan\Utilities\EtruscanConfig
source: src/Utilities/EtruscanConfig.php
layer: utility
context:
  - cli
  - mcp
  - usage
generated_by: etruscan
---

## Description

The typed reading of `config/etruscan.php`. Every key is read here and nowhere else, so a default
lives in exactly two places — the published config file and this class — instead of being
retyped at each call site, where one stale literal was enough to send a command and a tool to
different vaults. Returning settled types also stops `mixed` leaking out of `config()` into the rest
of the package, which is what kept static analysis pinned below level 9.

## References

- [[absolute-path-resolver]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[scanned-folder-resolver]]

## Referenced by

- [[consultation-recorder]]
- [[etruscan-check]]
- [[etruscan-generate]]
- [[etruscan-graph]]
- [[etruscan-usage]]
- [[lookup-node]]
- [[map-overview]]
- [[map-reader]]
- [[search-map]]
- [[trace-node]]
