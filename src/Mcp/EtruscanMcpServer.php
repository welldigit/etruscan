<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp;

use Laravel\Mcp\Server;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Mcp\Tools\LookupNode;
use WellDigit\Etruscan\Mcp\Tools\MapOverview;
use WellDigit\Etruscan\Mcp\Tools\SearchMap;
use WellDigit\Etruscan\Mcp\Tools\TraceNode;

#[EtruscanNode('etruscan-mcp-server')]
#[EtruscanLayer('server')]
#[EtruscanContext('mcp')]
final class EtruscanMcpServer extends Server
{
    protected string $name = 'Etruscan';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'TEXT'
        Etruscan serves this project's codebase map: one node per meaningful class,
        with a human-written description, taxonomy axes, the source file path, and
        dependency links in both directions. Consult the map BEFORE crawling source
        files. Use map-overview to orient in the codebase, search-map to find nodes
        by name or concept, lookup-node to read one node in full (then open its
        source path), and trace-node to follow dependencies. Lookups are recorded
        locally so the team can see which nodes matter and what is missing from the
        map — nothing leaves the machine.
        TEXT;

    /**
     * @var array<int, class-string<Server\Tool>>
     */
    protected array $tools = [
        MapOverview::class,
        SearchMap::class,
        LookupNode::class,
        TraceNode::class,
    ];
}
