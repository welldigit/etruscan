---
alias: scanned-folder-resolver
class: ScannedFolderResolver
fqcn: WellDigit\Etruscan\Utilities\ScannedFolderResolver
source: src/Utilities/ScannedFolderResolver.php
layer: utility
context: scan
generated_by: etruscan
---

## Description

Normalizes the `scanned_folders` config into a resolved folder list: accepts either a PHP array or a
comma-separated string (from the env override), trims and de-duplicates entries, and resolves each
through [[absolute-path-resolver]].

## References

- [[absolute-path-resolver]]

## Referenced by

- [[etruscan-config]]
