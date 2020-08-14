<?php

use Kirschbaum\EloquentPowerJoins\Tests\Models\Category;
use Kirschbaum\EloquentPowerJoins\Tests\Models\Post;
use Kirschbaum\EloquentPowerJoins\Tests\Models\User;

$factory->define(Post::class, function (Faker\Generator $faker) {
    return [
        'user_id' => factory(User::class),
        'category_id' => factory(Category::class),
        'title' => $faker->words(3, true),
    ];
});

$factory->state(Post::class, 'published', ['published' => true]);
$factory->state(Post::class, 'unpublished', ['published' => false]);
