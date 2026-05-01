<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Location\GetCitiesRequest;
use App\Http\Requests\Api\Location\GetStatesRequest;
use App\Services\Location\LocationService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    protected LocationService $locationService;

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
    public function getStatesByCountry(GetStatesRequest $request)
    {
        $countryId = $request->query('country_id');

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
    public function getCitiesByState(GetCitiesRequest $request)
    {
        $stateId = $request->query('state_id');
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
