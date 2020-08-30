<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    use PowerJoins;

    /** @var string */
    protected $table = 'categories';

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function publishedPosts(): HasMany
    {
        return $this->hasMany(Post::class)->published();
    }
}
