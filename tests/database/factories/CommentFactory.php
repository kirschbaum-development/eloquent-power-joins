<?php

use Illuminate\Support\Str;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Post;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\User;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Comment;

$factory->define(Comment::class, function (Faker\Generator $faker) {
    return [
        'user_id' => factory(User::class),
        'post_id' => factory(Post::class),
        'body' => $faker->words(3, true),
    ];
});
