<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Services\Location\LocationService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    protected $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Get all countries
     */
    public function getCountries()
    {
        $countries = $this->locationService->getAllCountries();

        return response()->json([
            'success' => true,
            'data' => $countries,
            'message' => 'Countries retrieved successfully'
        ]);
    }

    /**
     * Get states by country ID
     */
    public function getStatesByCountry($countryId)
    {
        $states = $this->locationService->getStatesByCountry($countryId);

        if ($states->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No states found for this country'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $states,
            'message' => 'States retrieved successfully'
        ]);
    }

    /**
     * Get cities by state ID
     */
    public function getCitiesByState($stateId)
    {
        $cities = $this->locationService->getCitiesByState($stateId);

        if ($cities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No cities found for this state'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cities,
            'message' => 'Cities retrieved successfully'
        ]);
    }
}
