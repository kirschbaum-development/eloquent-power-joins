<?php

namespace KirschbaumDevelopment\LaravelWhereHasWithJoins;

use Illuminate\Database\Eloquent\Builder;

class LaravelWhereHasWithJoins
{
    public static function registerEloquentMacros()
    {
        Builder::macro('whereHasWithJoins', function ($relation) {

        });
    }
}
