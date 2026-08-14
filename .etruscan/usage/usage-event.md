---
alias: usage-event
class: UsageEvent
fqcn: WellDigit\Etruscan\Payloads\UsageEvent
source: src/Payloads/UsageEvent.php
layer: payload
context: usage
generated_by: etruscan
---

## Description

One recorded map consultation: which tool, hit or miss, the exact subject the agent asked for, how
many results came back, the size of the served answer in characters, and a server-stamped timestamp.
The atom the usefulness report is built from.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[usage-event-type]]
- [[usage-outcome]]

## Referenced by

- [[consultation-recorder]]
- [[usage-log-reader]]
- [[usage-recorder]]
- [[usage-report-builder]]
