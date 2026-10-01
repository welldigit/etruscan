---
alias: usage-recorder
class: UsageRecorder
fqcn: WellDigit\Etruscan\Services\UsageRecorder
source: src/Services/UsageRecorder.php
layer: service
context: usage
generated_by: etruscan
---

## Description

Appends one JSON line per consultation to the usage log under an exclusive lock — whole lines,
never interleaved bytes. Best-effort by design: measurement must never break the tool being
measured.

## References

- [[reports-directory-preparer]]
- [[usage-event]]

## Referenced by

- [[consultation-recorder]]
- [[usage-log-reader]]
