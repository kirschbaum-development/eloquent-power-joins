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

    public function assertQueryContains(string $expected, string $actual, string $message = '', ?int $times = null): void
    {
        $actual = str_replace(['`', '"'], '', $actual);
        $expected = str_replace(['`', '"'], '', $expected);

        if (is_int($times)) {
            $this->assertEquals(
                $times,
                substr_count($actual, $expected),
                $message
            );

            return;
        }

        $this->assertStringContainsString($expected, $actual, $message);
    }

    public function assertQueryNotContains(string $expected, string $actual, string $message = '', ?int $times = null): void
    {
        $actual = str_replace(['`', '"'], '', $actual);
        $expected = str_replace(['`', '"'], '', $expected);

        if (is_int($times)) {
            $this->assertNotEquals(
                $times,
                substr_count($actual, $expected),
                $message
            );

            return;
        }

        $this->assertStringNotContainsString($expected, $actual, $message);
    }
}
