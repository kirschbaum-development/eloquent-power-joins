<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    protected $table = 'addresses';

    protected $fillable = [
        'kvh_code',
        'name',
    ];

    public function requested_addresses(): HasMany
    {
        return $this->hasMany(RequestedAddress::class, 'kvh_code', 'kvh_code');
    }

    /**
     * Get the latest requested address for this access address.
     *
     * @return HasOne<RequestedAddress, $this>
     */
    public function latest_requested_address(): HasOne
    {
        return $this->requested_addresses()
            ->one()
            ->latestOfMany('requested_at');
    }
}
