<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Kirschbaum\PowerJoins\Tests\Models\Builder\PostBuilder;

class Post extends Model
{
    use PowerJoins;
    use SoftDeletes;

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

    public function lastComment(): HasOne
    {
        return $this
            ->hasOne(Comment::class)
            ->ofMany();
    }

    public function bestComment(): HasOne
    {
        return $this
            ->hasOne(Comment::class)
            ->ofMany('votes', 'max');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function coverImages(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->where('cover', true);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopePublished($query)
    {
        $query->where('posts.published', true);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class);
    }

    public function newEloquentBuilder($query): PostBuilder
    {
        return new PostBuilder($query);
    }
}
