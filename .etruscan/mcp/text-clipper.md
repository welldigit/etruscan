---
alias: text-clipper
class: TextClipper
fqcn: WellDigit\Etruscan\Utilities\TextClipper
source: src/Utilities/TextClipper.php
layer: utility
context: mcp
generated_by: etruscan
---

## Description

Clips served text to a character budget, multibyte throughout. It exists because the byte-based clip
it replaced could cut through an em dash: the result is invalid UTF-8, json_encode then returns
false, and laravel/mcp's `?: ''` turns that into a zero-length JSON-RPC frame, so the tool call
returns nothing and raises nothing. Every clip on the serving path goes through here, so that
failure has exactly one place to not happen. The ellipsis is spent from inside the budget, which
makes a stated limit a true ceiling rather than a ceiling plus three; the line-boundary variant
backs up to the last break so a block of human markdown is never cut mid-line.

## Referenced by

- [[lookup-node]]
- [[node-summary-line]]
