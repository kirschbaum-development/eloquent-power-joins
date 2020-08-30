<?php

use Carbon\Carbon;
use Kirschbaum\PowerJoins\Tests\Models\User;

$factory->define(User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
    ];
});

$factory->state(User::class, 'trashed', [
    'deleted_at' => Carbon::now(),
]);

$factory->state(User::class, 'rockstar', [
    'rockstar' => true,
]);
