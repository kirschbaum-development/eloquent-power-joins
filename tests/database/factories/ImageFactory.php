<?php

use Carbon\Carbon;
use Kirschbaum\EloquentPowerJoins\Tests\Models\Post;
use Kirschbaum\EloquentPowerJoins\Tests\Models\Image;

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
