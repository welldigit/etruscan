---
alias: search-map
class: SearchMap
fqcn: WellDigit\Etruscan\Mcp\Tools\SearchMap
extends: Laravel\Mcp\Server\Tool
source: src/Mcp/Tools/SearchMap.php
layer: tool
context: mcp
generated_by: etruscan
---

## Description

MCP tool: ranked search over aliases, class names, axis values, descriptions and human notes. A
query with zero results is recorded as a miss with the agent's exact words — the sharpest
annotation signal the system produces.

## References

- [[consultation-recorder]]
- [[etruscan-config]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[identity-frontmatter-key]]
- [[map-reader]]
- [[map-search]]
- [[parsed-note]]
- [[usage-event-type]]
- [[usage-outcome]]

## Referenced by

- [[etruscan-mcp-server]]
- [[etruscan-service-provider]]
