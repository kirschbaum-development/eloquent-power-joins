<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $table = 'cities';

    protected $fillable = [
        'name',
        'country_id',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function inCountry($query, CountryEnum $country)
    {
        $countryId = Country::select('id')->where('iso', $country->value)->valueOrFail('id');

        return $query->where('cities.country_id', $countryId);
    }
}
