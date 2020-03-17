<?php

namespace KirschbaumDevelopment\LaravelWhereHasWithJoins;

use Illuminate\Support\ServiceProvider;

class LaravelWhereHasWithJoinsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {


        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-where-has-with-joins.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-where-has-with-joins');

        LaravelWhereHasWithJoins::registerEloquentMacros();
    }
}
