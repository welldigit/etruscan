---
alias: usage-log-reader
class: UsageLogReader
fqcn: WellDigit\Etruscan\Services\UsageLogReader
source: src/Services/UsageLogReader.php
layer: service
context: usage
generated_by: etruscan
---

## Description

Tolerant JSONL reader for the usage log: malformed lines and newer-schema lines are skipped and
counted, never fatal — the report always states what it could not read.

## References

- [[usage-event]]
- [[usage-event-type]]
- [[usage-outcome]]
- [[usage-recorder]]

## Referenced by

- [[etruscan-usage]]
