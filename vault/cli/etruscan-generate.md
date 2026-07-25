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

The projector: scan roots, build notes, write the vault. Options: --vault, --group-by (comma-separated keys nest folders; "none" forces flat), --purge, --dry-run.

## References

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
- [[target-folder-counter]]
- [[vault-writer]]
