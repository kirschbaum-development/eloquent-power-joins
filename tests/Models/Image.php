<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use PowerJoins;

    /** @var string */
    protected $table = 'images';

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
