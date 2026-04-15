<?php

namespace Kirschbaum\PowerJoins\Tests\Models\CrossConnection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Author extends Model
{
    use SoftDeletes;

    protected $connection = 'testing';

    protected $table = 'authors';

    public $timestamps = false;

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function publishedArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id')->where('articles.published', true);
    }

    public function articlesWithTrashed(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id')->withTrashed();
    }

    public function articlesOnlyTrashed(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id')->onlyTrashed();
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'author_tag', 'author_id', 'tag_id');
    }

    public function stickers(): MorphMany
    {
        return $this->morphMany(Sticker::class, 'stickerable');
    }

    public function labels(): MorphToMany
    {
        return $this->morphToMany(Label::class, 'labelable', 'labelables', 'labelable_id', 'label_id');
    }

    public function commentsThroughArticles(): HasManyThrough
    {
        return $this->hasManyThrough(Comment::class, Article::class, 'author_id', 'article_id');
    }

    public function scopeHasPublishedArticles($query)
    {
        $query->powerJoinWhereHas('articles', function ($join) {
            $join->where('articles.published', true);
        });
    }
}
