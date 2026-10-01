---
alias: etruscan-export
class: EtruscanExportCommand
fqcn: WellDigit\Etruscan\Commands\EtruscanExportCommand
extends: Illuminate\Console\Command
source: src/Commands/EtruscanExportCommand.php
layer: command
context:
  - export
  - cli
generated_by: etruscan
---

## Description

Writes the map as one markdown index, for an agent to import into CLAUDE.md. The map tools answer
questions on demand; the index answers the one every session opens with — what is in this codebase
— before anything is asked, from the prompt prefix, at cache rates, costing no tool call. Writes
to the vault root rather than `.reports/`, because this is the one artifact a consumer is meant to
commit. `--stdout` prints instead, and the output is deterministic so re-exporting an unchanged map
never dirties a diff.

## References

- [[absolute-path-resolver]]
- [[digest-path-resolver]]
- [[etruscan-config]]
- [[map-digest-renderer]]
- [[map-reader]]
- [[vault-reader]]

## Referenced by

- [[etruscan-service-provider]]
