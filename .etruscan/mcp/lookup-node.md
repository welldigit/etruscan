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

- [[consultation-recorder]]
- [[etruscan-config]]
- [[identity-frontmatter-key]]
- [[map-reader]]
- [[node-lookup]]
- [[note-trust-reminder]]
- [[parsed-note]]
- [[text-clipper]]
- [[truncation-notice]]
- [[usage-event-type]]
- [[usage-outcome]]

## Referenced by

- [[etruscan-mcp-server]]
- [[etruscan-service-provider]]
- [[etruscan-usage]]
