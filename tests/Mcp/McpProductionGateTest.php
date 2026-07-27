<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Tests\Mcp;

use Illuminate\Foundation\Application;
use Laravel\Mcp\Facades\Mcp;
use WellDigit\Etruscan\Tests\TestCase;

final class McpProductionGateTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['env'] = 'production';
    }

    public function test_the_mcp_server_is_not_registered_in_production(): void
    {
        $this->assertSame('production', $this->app?->environment());
        $this->assertNull(Mcp::getLocalServer('etruscan'));
    }
}
