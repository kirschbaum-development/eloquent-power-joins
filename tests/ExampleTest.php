<?php

namespace KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests;

use Orchestra\Testbench\TestCase;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\LaravelWhereHasWithJoinsServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [LaravelWhereHasWithJoinsServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
