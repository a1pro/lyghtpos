<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class State extends Model
{
    protected $table = 'states';

    protected $primaryKey = 'id_state';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false; // Since table doesn't have timestamps

    protected $fillable = [
        'state',
        'country_id',
        'is_active',
        'sort_order',
        'lang'
    ];

    protected $casts = [
        'id_state' => 'integer',
        'country_id' => 'integer',
        'is_active' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the country that owns this state.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'country_id');
    }

    /**
     * Get cities for this state.
     */
    public function cities()
    {
        return $this->hasMany(City::class, 'state_id', 'id_state');
    }

    /**
     * Scope ordered by sort order then name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('state', 'asc');
    }
}
