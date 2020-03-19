<?php

use Illuminate\Support\Str;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests\Models\Post;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests\Models\User;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests\Models\Comment;

$factory->define(Comment::class, function (Faker\Generator $faker) {
    return [
        'user_id' => factory(User::class),
        'post_id' => factory(Post::class),
        'body' => $faker->words(3, true),
    ];
});
