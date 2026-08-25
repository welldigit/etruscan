---
alias: trace-node
class: TraceNode
fqcn: WellDigit\Etruscan\Mcp\Tools\TraceNode
extends: Laravel\Mcp\Server\Tool
source: src/Mcp/Tools/TraceNode.php
layer: tool
context: mcp
generated_by: etruscan
---

## Description

MCP tool: one dependency hop from a node — what it references, what references it, or both —
each neighbour with its one-line description, so the agent decides the next hop without opening
files.

## References

- [[consultation-recorder]]
- [[etruscan-config]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[map-reader]]
- [[node-trace]]
- [[note-trust-reminder]]
- [[trace-direction]]
- [[usage-event-type]]
- [[usage-outcome]]

## Referenced by

- [[etruscan-mcp-server]]
- [[etruscan-service-provider]]
