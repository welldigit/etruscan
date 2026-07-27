---
alias: lookup-node
class: LookupNode
fqcn: WellDigit\Etruscan\Mcp\Tools\LookupNode
extends: Laravel\Mcp\Server\Tool
source: src/Mcp/Tools/LookupNode.php
layer: tool
context: mcp
generated_by: etruscan
---

## Description

MCP tool: one node in full — frontmatter identity, description, both edge directions, protected
human notes, ending with the source path to open. An unknown alias records a ground-truth miss and
suggests the closest aliases.

## References

- [[absolute-path-resolver]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[identity-frontmatter-key]]
- [[node-lookup]]
- [[parsed-note]]
- [[usage-event]]
- [[usage-event-type]]
- [[usage-log-path-resolver]]
- [[usage-outcome]]
- [[usage-recorder]]
- [[vault-reader]]

## Referenced by

- [[etruscan-mcp-server]]
