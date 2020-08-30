<?php

use Carbon\Carbon;
use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\Image;

$factory->define(Image::class, function (Faker\Generator $faker) {
    return [
        'cover' => false,
    ];
});

$factory->state(Image::class, 'owner:post', [
    'imageable_type' => Post::class,
    'imageable_id' => factory(Post::class),
]);

$factory->state(Image::class, 'cover', [
    'cover' => 1,
]);
