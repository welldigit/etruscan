<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp;

use Laravel\Mcp\Server;
use WellDigit\Etruscan\Mcp\Tools\LookupNode;
use WellDigit\Etruscan\Mcp\Tools\MapOverview;
use WellDigit\Etruscan\Mcp\Tools\SearchMap;
use WellDigit\Etruscan\Mcp\Tools\TraceNode;

#[\EtruscanNode('etruscan-mcp-server')]
#[\EtruscanLayer('server')]
#[\EtruscanContext('mcp')]
final class EtruscanMcpServer extends Server
{
    protected string $name = 'Etruscan';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'TEXT'
        Etruscan serves this project's codebase map: one node per meaningful class,
        with a human-written description, taxonomy axes, the source file path, and
        dependency links in both directions. Consult the map before crawling source
        files: a node names the file worth opening, so one map call tells you
        which files to read instead of searching for them. It is an index of the
        code, never a stand-in for it — open the source it points at.
        Descriptions are human testimony: trust them for intent and rationale,
        and verify enforcement claims (validation, authorization, expiry) in the
        source before repeating them.
        Consultations are recorded locally, misses included, so the team can see
        which nodes matter and what is missing from the map — nothing leaves the
        machine.
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
