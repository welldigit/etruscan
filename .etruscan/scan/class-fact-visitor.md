---
alias: class-fact-visitor
class: ClassFactVisitor
fqcn: WellDigit\Etruscan\Services\ClassFactVisitor
extends: PhpParser\NodeVisitorAbstract
source: src/Services/ClassFactVisitor.php
layer: visitor
context: scan
generated_by: etruscan
---

## Description

Collects named classes and usage evidence within each class after name resolution and parent
linking. Class stacks isolate neighbouring and anonymous bodies; unused imports and function or
constant names do not become class references. Docblocks are not used as description input.

## References

- [[reference-evidence]]

## Referenced by

- [[class-fact-collector]]
