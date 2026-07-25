<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use WellDigit\Etruscan\EtruscanServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [EtruscanServiceProvider::class];
    }
}
