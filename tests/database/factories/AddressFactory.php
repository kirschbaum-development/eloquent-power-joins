<?php

use Kirschbaum\PowerJoins\Tests\Models\Address;

$factory->define(Address::class, function (Faker\Generator $faker) {
    return [
        'kvh_code' => $faker->unique()->bothify('???###'),
        'name' => $faker->streetName,
        'city_id' => null,
    ];
});
