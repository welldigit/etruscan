---
alias: reports-directory-preparer
class: ReportsDirectoryPreparer
fqcn: WellDigit\Etruscan\Utilities\ReportsDirectoryPreparer
source: src/Utilities/ReportsDirectoryPreparer.php
layer: utility
context: usage
generated_by: etruscan
---

## Description

Creates the `.reports` folder on first use and seeds it with a self-ignoring `.gitignore` (`*` plus
`!.gitignore`), so usage data and rendered reports stay out of git by default — the local-only
promise enforced by the filesystem, not by documentation. The seed is written only when the file is
missing: a team that wants to commit the log edits the file, and their edit is never overwritten.

## Referenced by

- [[etruscan-graph]]
- [[etruscan-usage]]
- [[reference-index]]
- [[usage-recorder]]
