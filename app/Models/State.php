<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $table = 'states';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'country_id'
    ];

    protected $casts = [
        'id' => 'integer',
        'country_id' => 'integer',
    ];

    /**
     * Get the country that owns this state.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    /**
     * Get cities for this state.
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'state_id', 'id');
    }

    /**
     * Scope ordered by name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }
}
