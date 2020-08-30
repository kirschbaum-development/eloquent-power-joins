<?php

use Kirschbaum\PowerJoins\Tests\Models\Group;

$factory->define(Group::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
    ];
});
