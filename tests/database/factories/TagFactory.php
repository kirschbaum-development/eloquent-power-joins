<?php

use Kirschbaum\PowerJoins\Tests\Models\Tag;

$factory->define(Tag::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->words(3, true),
    ];
});
