<?php

namespace Kirschbaum\PowerJoins\Tests\Models\CrossConnection;

use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    protected $connection = 'secondary';

    protected $table = 'labels';

    public $timestamps = false;
}
