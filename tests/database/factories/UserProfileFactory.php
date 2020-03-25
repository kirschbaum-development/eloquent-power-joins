<?php

use Illuminate\Support\Str;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\User;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\UserProfile;

$factory->define(UserProfile::class, function (Faker\Generator $faker) {
    return [
        'user_id' => factory(User::class),
        'city' => $faker->city,
    ];
});
