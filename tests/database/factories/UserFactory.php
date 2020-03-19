<?php

use Illuminate\Support\Str;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests\Models\User;

$factory->define(User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
    ];
});
