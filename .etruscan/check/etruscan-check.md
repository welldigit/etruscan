---
alias: etruscan-check
class: EtruscanCheckCommand
fqcn: WellDigit\Etruscan\Commands\EtruscanCheckCommand
extends: Illuminate\Console\Command
source: src/Commands/EtruscanCheckCommand.php
layer: command
context:
  - check
  - cli
generated_by: etruscan
---

## Description

Audits the map for the mistakes annotations invite: duplicate aliases, attributes that resolve to no
class because a leading backslash or an import is missing, off-vocabulary axis values, dangling
wikilinks, and an exported index that has fallen behind the notes it indexes.

## References

- [[broken-link-checker]]
- [[check-category]]
- [[check-finding]]
- [[check-severity]]
- [[codebase-scanner]]
- [[digest-staleness-checker]]
- [[duplicate-alias-checker]]
- [[etruscan-config]]
- [[node-graph-builder]]
- [[structural-map-checker]]
- [[unresolved-attribute-checker]]
- [[vocabulary-checker]]

## Referenced by

- [[etruscan-service-provider]]
