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

The single owner of the line appended to every lookup-node and trace-node answer: a cue, at the
point of use, to check an enforcement claim against the code before repeating it. One constant so
the two tools can never drift into different trust stories.

Added after an A/B run showed a map-reading agent repeating a note's overstated validation claim: a
rule read once at session start had faded 39 tool calls in, so the reminder now travels with every
note served. Two things that run was never able to settle, recorded here so the next reader does not
have to rediscover them: the model it was observed on was not written down, and the change shipped
in one commit with the claim-class rewrite of both skills and the guideline sentence, so the
footer's own contribution was never isolated from the standing rules that landed beside it.

Shortened in 1.3.0 from 187 bytes to 105 by dropping the half that re-taught what a description is
— a definition already standing once per session in the server instructions, the guideline and the
navigate skill — and keeping only the pointer at the note just served. Whether the cue earns its
remaining tokens on current models is still open; settling it needs one arm, not an A/B: serve a
note whose enforcement claim contradicts its source, read it past tool call 40 with the skill
unloaded, and count how often the claim is repeated unverified.

## Referenced by

- [[lookup-node]]
- [[trace-node]]
