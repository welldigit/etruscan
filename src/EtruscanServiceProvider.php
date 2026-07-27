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
    }
}
