<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Awobaz\Compoships\Compoships;
use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Post extends Model
{
    use PowerJoins;
    use Compoships;

    /** @var string */
    protected $table = 'posts';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rockstarUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->where('users.rockstar', true);
    }

    public function userWithTrashed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function coverImages(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->where('cover', true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function userComments(): HasMany
    {
        return $this->hasMany(
            Comment::class,
            ['id', 'user_id'],
            ['post_id', 'user_id']
        );
    }

    public function scopePublished($query)
    {
        $query->where('posts.published', true);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class);
    }
}
