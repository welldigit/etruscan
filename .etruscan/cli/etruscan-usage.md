---
alias: etruscan-usage
class: EtruscanUsageCommand
fqcn: WellDigit\Etruscan\Commands\EtruscanUsageCommand
extends: Illuminate\Console\Command
source: src/Commands/EtruscanUsageCommand.php
layer: command
context:
  - usage
  - cli
generated_by: etruscan
---

## Description

The usefulness report: consultations per tool, most-consulted nodes, and the misses that are
pre-validated annotation candidates. --days windows it, --json feeds dashboards.

## References

- [[absolute-path-resolver]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[usage-log-path-resolver]]
- [[usage-log-reader]]
- [[usage-page-renderer]]
- [[usage-report]]
- [[usage-report-builder]]

## Referenced by

- [[etruscan-service-provider]]
