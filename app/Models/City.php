<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    protected $table = 'cities';

    protected $primaryKey = 'id_city';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'city',
        'state_id',
        'is_default',
        'is_active',
        'sort_order',
        'lang',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id_city' => 'integer',
        'state_id' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the state that owns this city.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id', 'id_state');
    }

    /**
     * Scope ordered by sort order then city name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('city', 'asc');
    }
}
