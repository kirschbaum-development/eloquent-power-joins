<?php

use Kirschbaum\EloquentPowerJoins\Tests\Models\User;

$factory->define(User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
    ];
});
