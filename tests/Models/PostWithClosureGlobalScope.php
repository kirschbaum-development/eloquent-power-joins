<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

class PostWithClosureGlobalScope extends Post
{
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('published', function ($builder) {
            $builder->where('posts.published', true);
        });
    }
}
