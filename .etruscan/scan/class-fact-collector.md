---
alias: class-fact-collector
class: ClassFactCollector
fqcn: WellDigit\Etruscan\Services\ClassFactCollector
source: src/Services/ClassFactCollector.php
layer: service
context: scan
generated_by: etruscan
---

## Description

Collects one file's class facts from its AST: drives a fresh [[class-fact-visitor]] behind a
NameResolver, so the visitor's traversal state never leaks past a single invocation.

## References

- [[class-fact-visitor]]

## Referenced by

- [[codebase-scanner]]
