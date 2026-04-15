<?php

namespace Kirschbaum\PowerJoins\Tests\Models\CrossConnection;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $connection = 'secondary';

    protected $table = 'tags';

    public $timestamps = false;
}
