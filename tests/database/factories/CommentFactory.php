<?php

use Kirschbaum\EloquentPowerJoins\Tests\Models\Comment;
use Kirschbaum\EloquentPowerJoins\Tests\Models\Post;
use Kirschbaum\EloquentPowerJoins\Tests\Models\User;

$factory->define(Comment::class, function (Faker\Generator $faker) {
    return [
        'user_id' => factory(User::class),
        'post_id' => factory(Post::class),
        'body' => $faker->words(3, true),
    ];
});

$factory->state(Comment::class, 'approved', ['approved' => 1]);
$factory->state(Comment::class, 'unapproved', ['approved' => 0]);
