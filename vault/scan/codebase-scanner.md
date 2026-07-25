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

Walks the configured roots and statically parses every PHP file into [[scanned-class]] facts. Nothing is autoloaded or executed; unparseable files are logged and skipped, and missing roots are ignored harmlessly.

## References

- [[axis-attribute-reader]]
- [[class-fact-collector]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[scanned-class]]
