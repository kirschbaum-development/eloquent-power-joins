<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Kirschbaum\PowerJoins\Tests\Models\Scopes\PublishedScope;

class PostWithGlobalScope extends Post
{
    protected static function booted()
    {
        static::addGlobalScope(new PublishedScope());
    }
}
