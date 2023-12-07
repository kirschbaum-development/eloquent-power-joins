<?php

use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\PostStat;

$factory->define(PostStat::class, function (Faker\Generator $faker) {
    return [
        'post_id' => factory(Post::class),
        'data' => [],
    ];
});
