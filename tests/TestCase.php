<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tests wipe the database (RefreshDatabase). The local `mobilehub`
     * database holds a copy of production, so refuse to run against
     * anything but the dedicated test database — e.g. if a cached config
     * (bootstrap/cache/config.php) overrides phpunit.xml. Checked here,
     * before setUp() runs RefreshDatabase.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $config   = $app['config'];
        $database = $config->get('database.connections.' . $config->get('database.default') . '.database');
        if ($database !== 'mobilehub_testing') {
            fwrite(STDERR, "\nRefusing to run tests against database '{$database}'. Run `php artisan config:clear` first.\n");
            exit(1);
        }

        return $app;
    }
}
