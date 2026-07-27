---
alias: etruscan-mcp-server
class: EtruscanMcpServer
fqcn: WellDigit\Etruscan\Mcp\EtruscanMcpServer
extends: Laravel\Mcp\Server
source: src/Mcp/EtruscanMcpServer.php
layer: server
context: mcp
generated_by: etruscan
---

## Description

The MCP server agents connect to (registered as local server `etruscan`, started by
[[etruscan-mcp]]). Its instructions teach the agent the tool-per-question mapping; its four
read-only tools serve the vault on disk — including human notes — so the map answers
structurally instead of being globbed.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]
- [[lookup-node]]
- [[map-overview]]
- [[search-map]]
- [[trace-node]]

## Referenced by

- [[etruscan-service-provider]]
