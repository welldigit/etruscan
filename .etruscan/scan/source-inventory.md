---
alias: source-inventory
class: SourceInventory
fqcn: WellDigit\Etruscan\Services\SourceInventory
source: src/Services/SourceInventory.php
layer: service
context: scan
generated_by: etruscan
---

## Description

Hashes the PHP files in configured roots for source freshness checks, retaining missing roots in the
inventory. Content hashes detect edits even when timestamps are unchanged; file additions and
deletions change the inventory too.

## Referenced by

- [[source-freshness]]
