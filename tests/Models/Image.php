<?php

namespace Kirschbaum\EloquentPowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Kirschbaum\EloquentPowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use PowerJoins;

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
