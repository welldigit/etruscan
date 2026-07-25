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

php-parser visitor gathering the raw facts of one parsed file: each named class with its parent, docblock summary, and attributes, plus the file's class references — imports, type hints, instantiations, attributes. Runs after a NameResolver (replaceNodes: false), so names carry resolved FQCNs. Anonymous classes and function/const imports are skipped.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
