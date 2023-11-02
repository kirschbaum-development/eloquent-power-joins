<?php

namespace Kirschbaum\PowerJoins;

use Illuminate\Support\ServiceProvider;

class PowerJoinsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        EloquentJoins::registerEloquentMacros();
    }
}
