<?php

use Kirschbaum\PowerJoins\Tests\Models\Image;
use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;

$factory->define(Image::class, function (Faker\Generator $faker) {
    return [
        'cover' => false,
    ];
});

$factory->state(Image::class, 'owner:post', [
    'imageable_type' => Post::class,
    'imageable_id' => function () {
        return factory(Post::class)->create()->id;
    },
]);

$factory->state(Image::class, 'owner:user', [
    'imageable_type' => User::class,
    'imageable_id' => function () {
        return factory(User::class)->create()->id;
    },
]);

$factory->state(Image::class, 'cover', [
    'cover' => 1,
]);
