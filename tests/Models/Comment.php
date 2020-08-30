<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
