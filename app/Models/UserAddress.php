<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory;

    protected $table = 'user_addresses';

    protected $fillable = [
        'user_id',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['full_address'];

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get full address attribute.
     */
    public function getFullAddressAttribute()
    {
        $address = $this->address;

        $address .= ", {$this->city}";

        if ($this->state) {
            $address .= ", {$this->state}";
        }

        $address .= ", {$this->postal_code}, {$this->country}";

        return $address;
    }

    /**
     * Scope for active addresses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
