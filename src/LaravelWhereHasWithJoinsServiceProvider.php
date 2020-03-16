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
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-where-has-with-joins');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-where-has-with-joins');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-where-has-with-joins.php'),
            ], 'config');

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-where-has-with-joins');
    }
}
