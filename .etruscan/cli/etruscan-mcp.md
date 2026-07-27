---
alias: etruscan-mcp
class: EtruscanMcpCommand
fqcn: WellDigit\Etruscan\Commands\EtruscanMcpCommand
extends: Illuminate\Console\Command
source: src/Commands/EtruscanMcpCommand.php
layer: command
context:
  - mcp
  - cli
generated_by: etruscan
---

## Description

Starts the Etruscan MCP server over stdio — the command an agent's .mcp.json points at.

## References

- [[etruscan-context]]
- [[etruscan-layer]]
- [[etruscan-node]]

## Referenced by

- [[etruscan-service-provider]]
