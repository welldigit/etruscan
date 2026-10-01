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
- [[scanned-folder-resolver]]
- [[token-estimator]]

## Referenced by

- [[consultation-recorder]]
- [[digest-staleness-checker]]
- [[etruscan-check]]
- [[etruscan-export]]
- [[etruscan-generate]]
- [[etruscan-graph]]
- [[etruscan-usage]]
- [[lookup-node]]
- [[map-overview]]
- [[map-reader]]
- [[map-search]]
- [[reference-index]]
- [[search-map]]
- [[source-freshness]]
- [[source-reference-trace]]
- [[structural-map-checker]]
- [[token-estimator]]
- [[trace-node]]
- [[usage-page-renderer]]
