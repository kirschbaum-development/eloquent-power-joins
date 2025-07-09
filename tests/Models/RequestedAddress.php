<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestedAddress extends Model
{
    protected $table = 'requested_addresses';

    protected $fillable = [
        'kvh_code',
        'requested_at',
        'status',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'kvh_code', 'kvh_code');
    }
}
