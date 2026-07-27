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

- [[absolute-path-resolver]]
- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[node-trace]]
- [[trace-direction]]
- [[usage-event]]
- [[usage-event-type]]
- [[usage-log-path-resolver]]
- [[usage-outcome]]
- [[usage-recorder]]
- [[vault-reader]]

## Referenced by

- [[etruscan-mcp-server]]
