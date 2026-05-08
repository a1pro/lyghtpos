<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $table = 'countries';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'shortname',
        'name',
        'phonecode'
    ];

    protected $casts = [
        'id' => 'integer',
        'phonecode' => 'integer',
    ];

    /**
     * Get all states for this country.
     */
    public function states(): HasMany
    {
        return $this->hasMany(State::class, 'country_id', 'id');
    }

    /**
     * Get country by shortname (country code).
     */
    public function scopeByShortname($query, $code)
    {
        return $query->where('shortname', strtoupper($code));
    }

    /**
     * Get country by name.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }
}
