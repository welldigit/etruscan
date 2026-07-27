---
alias: usage-report-builder
class: UsageReportBuilder
fqcn: WellDigit\Etruscan\Services\UsageReportBuilder
source: src/Services/UsageReportBuilder.php
layer: service
context: usage
generated_by: etruscan
---

## Description

Pure aggregation of usage events into a [[usage-report]]: counts per tool, hits and misses, top
consulted nodes, missed subjects, empty searches. The time cutoff is passed in; this class never
touches the clock.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[usage-event]]
- [[usage-event-type]]
- [[usage-outcome]]
- [[usage-report]]

## Referenced by

- [[etruscan-usage]]
