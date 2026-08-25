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

Audits the map for the mistakes annotations invite: duplicate aliases, off-vocabulary axis values,
and dangling wikilinks.

## References

- [[broken-link-checker]]
- [[check-category]]
- [[check-finding]]
- [[check-severity]]
- [[codebase-scanner]]
- [[duplicate-alias-checker]]
- [[etruscan-config]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[node-graph-builder]]
- [[vocabulary-checker]]

## Referenced by

- [[etruscan-service-provider]]
