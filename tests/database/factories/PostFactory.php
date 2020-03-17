<?php

use Illuminate\Support\Str;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests\Models\Post;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests\Models\User;

$factory->define(Post::class, function (Faker\Generator $faker) {
    return [
        'user_id' => factory(User::class),
        'title' => $faker->words(3, true),
    ];
});
