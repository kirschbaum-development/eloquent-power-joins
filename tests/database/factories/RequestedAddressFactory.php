<?php

use Kirschbaum\PowerJoins\Tests\Models\RequestedAddress;

$factory->define(RequestedAddress::class, function (Faker\Generator $faker) {
    return [
        'kvh_code' => $faker->unique()->bothify('???###'),
        'requested_at' => $faker->dateTimeBetween('-1 year', 'now'),
        'status' => 'pending',
    ];
});
