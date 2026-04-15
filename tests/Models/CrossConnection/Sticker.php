<?php

namespace Kirschbaum\PowerJoins\Tests\Models\CrossConnection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Sticker extends Model
{
    protected $connection = 'secondary';

    protected $table = 'stickers';

    public $timestamps = false;

    public function stickerable(): MorphTo
    {
        return $this->morphTo();
    }
}
