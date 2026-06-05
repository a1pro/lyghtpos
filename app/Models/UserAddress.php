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
        'city', // city id
        'state', // state id
        'postal_code',
        'country', // country id
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['full_address', 'city_name', 'state_name', 'country_name'];

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the country associated with the address.
     */
    public function countryRelation()
    {
        return $this->belongsTo(Country::class, 'country', 'id');
    }

    /**
     * Get the state associated with the address.
     */
    public function stateRelation()
    {
        return $this->belongsTo(State::class, 'state', 'id');
    }

    /**
     * Get the city associated with the address.
     */
    public function cityRelation()
    {
        return $this->belongsTo(City::class, 'city', 'id');
    }

    /**
     * Get full address attribute.
     */
    public function getFullAddressAttribute()
    {
        $addressParts = [];

        if (!empty($this->address)) {
            $addressParts[] = trim($this->address);
        }

        $cityName = $this->getCityNameAttribute();
        if (!empty($cityName)) {
            $addressParts[] = $cityName;
        }

        $stateName = $this->getStateNameAttribute();
        if (!empty($stateName)) {
            $addressParts[] = $stateName;
        }

        if (!empty($this->postal_code)) {
            $addressParts[] = $this->postal_code;
        }

        $countryName = $this->getCountryNameAttribute();
        if (!empty($countryName)) {
            $addressParts[] = $countryName;
        }

        if (empty($addressParts)) {
            return '';
        }

        return implode(', ', $addressParts);
    }

    /**
     * Get city name accessor.
     */
    public function getCityNameAttribute()
    {
        if ($this->city && $this->cityRelation()->exists()) {
            return $this->cityRelation->name;
        }
        return null;
    }

    /**
     * Get state name accessor.
     */
    public function getStateNameAttribute()
    {
        if ($this->state && $this->stateRelation()->exists()) {
            return $this->stateRelation->name;
        }
        return null;
    }

    /**
     * Get country name accessor.
     */
    public function getCountryNameAttribute()
    {
        if ($this->country && $this->countryRelation()->exists()) {
            return $this->countryRelation->name;
        }
        return null;
    }

    /**
     * Scope for active addresses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by country ID.
     */
    public function scopeByCountry($query, $countryId)
    {
        return $query->where('country', $countryId);
    }

    /**
     * Scope to filter by state ID.
     */
    public function scopeByState($query, $stateId)
    {
        return $query->where('state', $stateId);
    }

    /**
     * Scope to filter by city ID.
     */
    public function scopeByCity($query, $cityId)
    {
        return $query->where('city', $cityId);
    }
}
