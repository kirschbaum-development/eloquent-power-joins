<?php

use Kirschbaum\EloquentPowerJoins\Tests\Models\Group;

$factory->define(Group::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
    ];
});
