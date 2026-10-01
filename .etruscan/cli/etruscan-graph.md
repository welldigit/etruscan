---
alias: etruscan-graph
class: EtruscanGraphCommand
fqcn: WellDigit\Etruscan\Commands\EtruscanGraphCommand
extends: Illuminate\Console\Command
source: src/Commands/EtruscanGraphCommand.php
layer: command
context: cli
generated_by: etruscan
---

## Description

Renders the interactive graph page from the same scan-and-build chain, attaching each node's human
description read from the vault notes — their only home. Default target is `.reports/graph.html`
inside the vault, --output overrides.

## References

- [[absolute-path-resolver]]
- [[alias-collision]]
- [[codebase-scanner]]
- [[etruscan-config]]
- [[graph-page-renderer]]
- [[node-graph-builder]]
- [[reports-directory-preparer]]
- [[reports-path-resolver]]
- [[reserved-axis-key]]
- [[vault-description-reader]]

## Referenced by

- [[etruscan-service-provider]]
