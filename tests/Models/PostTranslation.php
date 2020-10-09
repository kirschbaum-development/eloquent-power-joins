<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Kirschbaum\PowerJoins\PowerJoins;

class PostTranslation extends Model
{
    use PowerJoins;

    /** @var string */
    protected $table = 'post_translations';
}
