<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Comment extends Model
{
    use PowerJoins;

    /** @var string */
    protected $table = 'comments';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function postCategory(): HasOneThrough
    {
        return $this->hasOneThrough(
            Category::class,
            Post::class,
            'id',
            'id',
            'post_id',
            'category_id'
        );
    }
}
