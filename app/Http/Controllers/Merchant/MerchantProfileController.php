<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\UserAddress;
use App\Services\Merchant\MerchantProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MerchantProfileController extends Controller
{
    protected $merchantProfileService;

    public function __construct(MerchantProfileService $merchantProfileService)
    {
        $this->merchantProfileService = $merchantProfileService;
    }

    public function index()
    {
        $user = Auth::user();

        $profileData = $this->merchantProfileService->getProfileData($user);
        $address = $this->merchantProfileService->getUserAddress($user);
        $countries = Country::orderBy('country_name', 'asc')->get();

        $selectedCountryId = null;
        $selectedStateId = null;
        $selectedCityId = null;

        if ($address) {
            $country = Country::where('country_name', $address->country)->first();

            if ($country) {
                $selectedCountryId = $country->country_id;

                $state = State::where('country_id', $country->country_id)
                    ->where('state', $address->state)
                    ->first();

                if ($state) {
                    $selectedStateId = $state->id_state;

                    $city = City::where('state_id', $state->id_state)
                        ->where('city', $address->city)
                        ->first();

                    if ($city) {
                        $selectedCityId = $city->id_city;
                    }
                }
            }
        }

        return view('merchant.pages.profile', array_merge($profileData, [
            'address' => $address,
            'countries' => $countries,
            'selectedCountryId' => $selectedCountryId,
            'selectedStateId' => $selectedStateId,
            'selectedCityId' => $selectedCityId,
        ]));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $user = Auth::user();

            $result = $this->merchantProfileService->updateProfile(
                $user,
                $request->only(['first_name', 'last_name', 'phone'])
            );

            return redirect()
                ->route('merchant.profile.index')
                ->with($result['success'] ? 'success' : 'error', $result['message']);
        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());

            return redirect()
                ->route('merchant.profile.index')
                ->with('error', 'Failed to update profile');
        }
    }

    public function getStates($countryId)
    {
        try {
            $states = State::where('country_id', $countryId)
                ->where('is_active', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('state', 'asc')
                ->get([
                    'id_state',
                    'state',
                    'country_id',
                ]);

            return response()->json($states);
        } catch (\Exception $e) {
            Log::error('Error fetching states: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to load states',
            ], 500);
        }
    }

    public function getCities($stateId)
    {
        try {
            $cities = City::where('state_id', $stateId)
                ->where('is_active', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('city', 'asc')
                ->get([
                    'id_city',
                    'city',
                    'state_id',
                ]);

            return response()->json($cities);
        } catch (\Exception $e) {
            Log::error('Error fetching cities: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to load cities',
            ], 500);
        }
    }

    public function updateAddress(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:user_addresses,id',
            'country_id' => 'required|exists:countries,country_id',
            'state_id' => 'required|exists:states,id_state',
            'city_id' => 'required|exists:cities,id_city',
            'address' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $user = Auth::user();

            $address = UserAddress::where('id', $request->address_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$address) {
                return redirect()
                    ->route('merchant.profile.index')
                    ->with('error', 'Address not found!');
            }

            $country = Country::where('country_id', $request->country_id)->first();

            $state = State::where('id_state', $request->state_id)
                ->where('country_id', $request->country_id)
                ->first();

            $city = City::where('id_city', $request->city_id)
                ->where('state_id', $request->state_id)
                ->first();

            if (!$country || !$state || !$city) {
                return redirect()
                    ->route('merchant.profile.index')
                    ->with('error', 'Invalid location data!');
            }

            if ($request->boolean('is_active')) {
                UserAddress::where('user_id', $user->id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_active' => false]);
            }

            $address->update([
                'address' => $request->address,
                'city' => $city->city,
                'state' => $state->state,
                'postal_code' => $request->postal_code,
                'country' => $country->country_name,
                'is_active' => $request->boolean('is_active'),
            ]);

            return redirect()
                ->route('merchant.profile.index')
                ->with('success', 'Address updated successfully!');
        } catch (\Exception $e) {
            Log::error('Address update error: ' . $e->getMessage());

            return redirect()
                ->route('merchant.profile.index')
                ->with('error', 'Failed to update address: ' . $e->getMessage());
        }
    }
}