<?php

namespace Kirschbaum\PowerJoins\Tests\Models\CrossConnection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    protected $connection = 'secondary';

    protected $table = 'articles';

    public $timestamps = false;

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function authorWithTrashed(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'author_id')->withTrashed();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'article_id');
    }

    public function scopePublished($query)
    {
        $query->where('articles.published', true);
    }
}
