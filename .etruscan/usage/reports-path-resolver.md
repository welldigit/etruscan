---
alias: reports-path-resolver
class: ReportsPathResolver
fqcn: WellDigit\Etruscan\Utilities\ReportsPathResolver
source: src/Utilities/ReportsPathResolver.php
layer: utility
context: usage
generated_by: etruscan
---

## Description

Single owner of where generated artifacts live: `.reports` inside the vault. The graph page, the
usage dashboard and the usage log all resolve through this one rule, so the vault root stays pure
markdown and the machine-generated output sits in one predictable, hidden place.

## Referenced by

- [[etruscan-graph]]
- [[etruscan-usage]]
- [[usage-log-path-resolver]]
