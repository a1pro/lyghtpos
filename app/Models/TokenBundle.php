<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenBundle extends Model
{
    protected $fillable = [
        'tokens',
        'base_amount',
        'system_fee',
        'discounted',
        'status',
    ];

    protected $casts = [
        'discounted' => 'boolean',
    ];
}
