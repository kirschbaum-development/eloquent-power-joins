<?php

use Kirschbaum\PowerJoins\Tests\Models\Category;

$factory->define(Category::class, function (Faker\Generator $faker) {
    return [
        'title' => $faker->words(3, true),
    ];
});

$factory->state(Category::class, 'with:parent', [
    'parent_id' => factory(Category::class),
]);
