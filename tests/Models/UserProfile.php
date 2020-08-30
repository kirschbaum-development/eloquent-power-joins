<?php

namespace Kirschbaum\EloquentPowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Kirschbaum\EloquentPowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use PowerJoins;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
