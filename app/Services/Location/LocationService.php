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
        return Country::select('id', 'name', 'shortname')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get states by country ID
     */
    public function getStatesByCountry($countryId)
    {
        return State::select('id', 'name', 'country_id')
            ->where('country_id', $countryId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get cities by state ID
     */
    public function getCitiesByState($stateId)
    {
        return City::select('id', 'name', 'state_id')
            ->where('state_id', $stateId)
            ->orderBy('name')
            ->get();
    }
}
