<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class User extends Model
{
    use SoftDeletes;
    use PowerJoins;

    /** @var string */
    protected $table = 'users';

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_members', 'user_id', 'group_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function profileWithCity(): HasOne
    {
        return $this->hasOne(UserProfile::class)->whereNotNull('city');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function publishedPosts(): HasMany
    {
        return $this->hasMany(Post::class)->where(function ($query) {
            $query->where('published', true);
        });
    }

    public function publishedOrReviewedPosts(): HasMany
    {
        return $this->hasMany(Post::class)->where(function ($query) {
            $query
                ->where('published', true)
                ->orWhere('reviewed', true);
        });
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function commentsThroughPosts(): HasManyThrough
    {
        return $this->hasManyThrough(Comment::class, Post::class);
    }

    public function scopeHasPublishedPosts($query)
    {
        $query->powerJoinWhereHas('posts', function ($join) {
            $join->where('posts.published', true);
        });
    }
}
