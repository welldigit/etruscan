---
alias: consultation-recorder
class: ConsultationRecorder
fqcn: WellDigit\Etruscan\Mcp\Services\ConsultationRecorder
source: src/Mcp/Services/ConsultationRecorder.php
layer: service
context:
  - mcp
  - usage
generated_by: etruscan
---

## Description

One place where a map consultation becomes a usage event: the tracking gate, the log path, the
timestamp and the size of the answer. Every tool used to carry its own copy of this, identical but
for the event type. Tools hand over the text they are about to serve rather than a character count,
so what gets recorded is by construction the text that actually left the tool.

## References

- [[etruscan-config]]
- [[usage-event]]
- [[usage-event-type]]
- [[usage-log-path-resolver]]
- [[usage-outcome]]
- [[usage-recorder]]

## Referenced by

- [[lookup-node]]
- [[map-overview]]
- [[search-map]]
- [[trace-node]]
