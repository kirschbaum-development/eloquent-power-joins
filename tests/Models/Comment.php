<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Model;
use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use PowerJoins;
    use Compoships;

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

    public function userPost(): BelongsTo
    {
        return $this->belongsTo(
            Post::class,
            ['post_id', 'user_id'],
            ['id', 'user_id']
        );
    }
}
