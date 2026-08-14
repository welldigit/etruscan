<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use WellDigit\Etruscan\EtruscanServiceProvider;

/**
 * Pest binds a test closure to this class, so the scratch state a `beforeEach`
 * hangs on `$this` is really shared state of every test. Listed here as a
 * reader's index of what those closures may reach for — PHPStan resolves `$this`
 * inside a Pest closure to the pending call, not to this class, so these are
 * documentation for people and nothing more (see the ignores in phpstan.neon).
 *
 * - string $vaultPath         Temporary vault a test writes into.
 * - string $fixtureDirectory  Temporary source tree a test scans.
 * - string $logPath           Temporary usage log file.
 * - string $logDirectory      Directory holding the usage log.
 * - list<class-string> $mapTools
 */
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
