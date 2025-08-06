<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $table = 'countries';

    protected $fillable = [
        'name',
        'iso',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
