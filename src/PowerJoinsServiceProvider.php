<?php

namespace Kirschbaum\PowerJoins;

use Illuminate\Support\ServiceProvider;

class PowerJoinsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        EloquentJoins::registerEloquentMacros();
    }
}
