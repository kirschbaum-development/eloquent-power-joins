<?php

namespace Kirschbaum\PowerJoins\Tests\Models\CrossConnection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $connection = 'secondary';

    protected $table = 'comments';

    public $timestamps = false;

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
