---
alias: vocabulary-checker
class: VocabularyChecker
fqcn: WellDigit\Etruscan\Services\VocabularyChecker
source: src/Services/VocabularyChecker.php
layer: service
context: check
generated_by: etruscan
---

## Description

Guards axis values two ways: for an axis listed in the `vocabulary` config, values outside the
allowed set are errors (with the closest allowed value suggested); for free-form axes, values within
a small edit distance of each other are flagged as a likely typo fragmenting the taxonomy.

## References

- [[check-category]]
- [[check-finding]]
- [[check-severity]]

## Referenced by

- [[etruscan-check]]
