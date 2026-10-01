---
alias: token-estimator
class: TokenEstimator
fqcn: WellDigit\Etruscan\Utilities\TokenEstimator
source: src/Utilities/TokenEstimator.php
layer: utility
context: usage
generated_by: etruscan
---

## Description

Turns a served character count into the rough token count people actually reason about. The ratio is
a rule of thumb, not a tokenizer — which is exactly why it lives in one place: the text report,
the `--json` contract and the dashboard all quote the same number, and correcting the estimate is a
one-line change rather than a hunt across three surfaces.

## References

- [[etruscan-config]]

## Referenced by

- [[etruscan-config]]
- [[etruscan-usage]]
- [[usage-page-renderer]]
