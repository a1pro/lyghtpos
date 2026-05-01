<?php

namespace App\Services\Location;

use App\Models\Country;
use App\Models\State;
use App\Models\City;

class LocationService
{
    /**
     * Get all active countries
     */
    public function getAllCountries()
    {
        return Country::select('country_id', 'country_name', 'country_code')
            ->orderBy('country_name')->get();
    }

    /**
     * Get states by country ID
     */
    public function getStatesByCountry($countryId)
    {
        return State::select('id_state', 'state', 'country_id', 'sort_order')->where('country_id', $countryId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('state', 'asc')
            ->get();
    }

    /**
     * Get cities by state ID
     */
    public function getCitiesByState($stateId)
    {
        return City::select('id_city', 'city', 'state_id', 'sort_order')->where('state_id', $stateId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('city', 'asc')
            ->get();
    }
}
