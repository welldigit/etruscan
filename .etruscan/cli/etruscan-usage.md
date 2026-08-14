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

The usefulness report: consultations per tool, most-consulted nodes, the misses that are
pre-validated annotation candidates, and the context served (chars plus an estimated token count).
--days windows it, --json feeds dashboards, --html renders the self-contained dashboard page.

## References

- [[absolute-path-resolver]]
- [[etruscan-config]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[reports-directory-preparer]]
- [[reports-path-resolver]]
- [[token-estimator]]
- [[usage-log-path-resolver]]
- [[usage-log-reader]]
- [[usage-page-renderer]]
- [[usage-report]]
- [[usage-report-builder]]

## Referenced by

- [[etruscan-service-provider]]
