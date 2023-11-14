<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kirschbaum\PowerJoins\PowerJoins;

class UserProfile extends Model
{
    use PowerJoins;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
