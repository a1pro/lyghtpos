<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $table = 'countries';

    protected $primaryKey = 'country_id';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false; // Since table doesn't have timestamps columns

    protected $fillable = [
        'country_name',
        'country_code',
        'currency_name',
        'currency_symbol',
        'currency_code'
    ];

    // Add casts for proper data types
    protected $casts = [
        'country_id' => 'integer',
    ];

    /**
     * Get all states for this country.
     */
    public function states(): HasMany
    {
        return $this->hasMany(State::class, 'country_id', 'country_id');
    }

    /**
     * Get country by country code.
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('country_code', strtoupper($code));
    }

    /**
     * Get country by name.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('country_name', 'like', "%{$name}%");
    }
}
