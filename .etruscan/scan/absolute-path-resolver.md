---
alias: absolute-path-resolver
class: AbsolutePathResolver
fqcn: WellDigit\Etruscan\Utilities\AbsolutePathResolver
source: src/Utilities/AbsolutePathResolver.php
layer: utility
context:
  - vault
  - scan
generated_by: etruscan
---

## Description

Makes a single path absolute against the application root, unless it already is one (unix,
drive-letter, or UNC-style). The one place base_path() is ever applied — every config default, env
override, and CLI option for scan folders, vault path, and graph output funnels through here, so
relative and absolute inputs behave identically everywhere.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]

## Referenced by

- [[etruscan-config]]
- [[etruscan-graph]]
- [[etruscan-usage]]
- [[scanned-folder-resolver]]
