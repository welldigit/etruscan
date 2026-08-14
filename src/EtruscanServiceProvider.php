<?php

declare(strict_types=1);

namespace WellDigit\Etruscan;

use Laravel\Mcp\Facades\Mcp;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Commands\EtruscanCheckCommand;
use WellDigit\Etruscan\Commands\EtruscanCommand;
use WellDigit\Etruscan\Commands\EtruscanGraphCommand;
use WellDigit\Etruscan\Commands\EtruscanMcpCommand;
use WellDigit\Etruscan\Commands\EtruscanUsageCommand;
use WellDigit\Etruscan\Mcp\EtruscanMcpServer;
use WellDigit\Etruscan\Mcp\Tools\LookupNode;
use WellDigit\Etruscan\Mcp\Tools\MapOverview;
use WellDigit\Etruscan\Mcp\Tools\SearchMap;
use WellDigit\Etruscan\Mcp\Tools\TraceNode;

#[EtruscanNode('etruscan-service-provider')]
#[EtruscanLayer('provider')]
#[EtruscanContext('cli')]
final class EtruscanServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('etruscan')
            ->hasConfigFile()
            ->hasCommands(
                EtruscanCommand::class,
                EtruscanGraphCommand::class,
                EtruscanCheckCommand::class,
                EtruscanMcpCommand::class,
                EtruscanUsageCommand::class,
            );
    }

    public function packageBooted(): void
    {
        if ($this->app->environment('production')) {
            return;
        }

        Mcp::local('etruscan', EtruscanMcpServer::class);

        $this->exposeToolsThroughBoost();
    }

    /**
     * Laravel Boost advertises and executes third-party tools listed in
     * `boost.mcp.tools.include` inside its already-connected `laravel-boost`
     * MCP server — so in Boost projects the map tools work with zero setup,
     * no extra .mcp.json entry. Harmless when Boost is not installed: the
     * config key is simply never read.
     */
    private function exposeToolsThroughBoost(): void
    {
        // Filtered to strings before the merge: the existing value is whatever
        // the consumer's config holds, and a stray non-string would make
        // array_unique() try to stringify it and fail at boot.
        $configured = array_filter((array) config('boost.mcp.tools.include', []), is_string(...));

        config()->set('boost.mcp.tools.include', array_values(array_unique([
            ...$configured,
            MapOverview::class,
            SearchMap::class,
            LookupNode::class,
            TraceNode::class,
        ])));
    }
}
