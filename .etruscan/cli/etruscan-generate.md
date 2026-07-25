---
alias: etruscan-generate
class: EtruscanCommand
fqcn: WellDigit\Etruscan\Commands\EtruscanCommand
extends: Illuminate\Console\Command
source: src/Commands/EtruscanCommand.php
layer: command
context: cli
generated_by: etruscan
---

## Description

The projector: scan folders, build notes, write the vault. Options: --vault, --group-by
(comma-separated keys nest folders; "none" forces flat), --purge, --dry-run.

## References

- [[absolute-path-resolver]]
- [[alias-collision]]
- [[axis-key-parser]]
- [[codebase-scanner]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[invalid-grouping-value]]
- [[node-graph-builder]]
- [[note-content]]
- [[note-path-resolver]]
- [[reserved-axis-key]]
- [[scanned-folder-resolver]]
- [[target-folder-counter]]
- [[vault-writer]]

## Referenced by

- [[etruscan-service-provider]]
