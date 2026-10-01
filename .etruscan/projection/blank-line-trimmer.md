---
alias: blank-line-trimmer
class: BlankLineTrimmer
fqcn: WellDigit\Etruscan\Utilities\BlankLineTrimmer
source: src/Utilities/BlankLineTrimmer.php
layer: utility
context:
  - projection
  - vault
generated_by: etruscan
---

## Description

Trims a block down to its content lines without touching the indentation of the first one. Plain
`trim()` eats leading spaces along with leading newlines, which silently demotes the opening line of
an indented code block to prose and leaves the rest of the block indented. The renderer and the
parser sit on opposite ends of every regeneration, so both trim by line through here — that shared
rule is what lets a human description survive the round trip byte-for-byte.

## Referenced by

- [[markdown-note-renderer]]
- [[note-parser]]
