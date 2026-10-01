---
alias: digest-path-resolver
class: DigestPathResolver
fqcn: WellDigit\Etruscan\Utilities\DigestPathResolver
source: src/Utilities/DigestPathResolver.php
layer: utility
context: export
generated_by: etruscan
---

## Description

The one place that knows the index lives at `{vault}/map.md`. Pointedly outside `.reports/`, which
seeds its own `.gitignore` to keep machine-local artifacts out of git — exactly backwards for the
one file that should travel with the repo. Single owner so [[etruscan-export]] and
[[digest-staleness-checker]] cannot disagree about where it is.

## Referenced by

- [[digest-staleness-checker]]
- [[etruscan-export]]
- [[etruscan-generate]]
