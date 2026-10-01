---
alias: codebase-scanner
class: CodebaseScanner
fqcn: WellDigit\Etruscan\Services\CodebaseScanner
source: src/Services/CodebaseScanner.php
layer: service
context: scan
generated_by: etruscan
---

## Description

Parses PHP in configured roots into per-class facts, collecting content hashes and scan diagnostics.
Duplicate files from overlapping roots are scanned once. Unparseable files are logged and skipped
during discovery; generation checks those diagnostics before writing. Axis recognition uses Composer
autoloading.

## References

- [[axis-attribute-reader]]
- [[class-fact-collector]]
- [[scanned-class]]

## Referenced by

- [[etruscan-check]]
- [[etruscan-generate]]
- [[etruscan-graph]]
