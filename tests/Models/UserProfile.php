<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use PowerJoins;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
