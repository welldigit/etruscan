<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[Description('Start the Etruscan MCP server (usually from .mcp.json)')]
#[Signature('etruscan:mcp')]
#[EtruscanNode('etruscan-mcp')]
#[EtruscanLayer('command')]
#[EtruscanContext('mcp')]
#[EtruscanContext('cli')]
final class EtruscanMcpCommand extends Command
{
    public function handle(): int
    {
        return $this->call('mcp:start', ['handle' => 'etruscan']);
    }
}
