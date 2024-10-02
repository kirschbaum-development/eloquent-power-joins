<?php

namespace Kirschbaum\PowerJoins\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kirschbaum\PowerJoins\PowerJoinsServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->withFactories(__DIR__.'/database/factories');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [PowerJoinsServiceProvider::class];
    }

    public function assertQueryContains(string $expected, string $actual): void
    {
        $actual = str_replace(['`', '"'], '', $actual);
        $expected = str_replace(['`', '"'], '', $expected);

        $this->assertStringContainsString($expected, $actual);
    }
}
