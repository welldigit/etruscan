---
alias: note-trust-reminder
class: NoteTrustReminder
fqcn: WellDigit\Etruscan\Mcp\Tools\NoteTrustReminder
source: src/Mcp/Tools/NoteTrustReminder.php
layer: utility
context: mcp
generated_by: etruscan
---

## Description

The single owner of the trust-protocol line appended to every lookup-node and trace-node answer:
descriptions are testimony — authoritative for intent and rationale — while enforcement claims
(validation, authorization, expiry) must be verified in the source before being repeated. One
constant so the two tools can never drift into different trust stories. Added after an A/B run
showed a map-reading agent repeating a note's overstated validation claim: a rule read once at
session start had faded 39 tool calls in, so the reminder now travels with every note served.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]

## Referenced by

- [[lookup-node]]
- [[trace-node]]
