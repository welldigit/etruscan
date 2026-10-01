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

- [[alias-collision]]
- [[axis-key-parser]]
- [[codebase-scanner]]
- [[digest-path-resolver]]
- [[etruscan-config]]
- [[invalid-grouping-value]]
- [[map-digest-renderer]]
- [[node-graph-builder]]
- [[note-path-resolver]]
- [[reference-index]]
- [[reserved-axis-key]]
- [[target-folder-counter]]
- [[unresolved-attribute-checker]]
- [[vault-reader]]
- [[vault-writer]]

## Referenced by

- [[etruscan-service-provider]]
