<?php

namespace App\Http\Controllers\Api\V1\PAX\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PAX\Auth\PaxUpdateMerchantAddressRequest;
use App\Services\Api\PAX\Merchant\PaxMerchantProfileService;
use Illuminate\Support\Facades\Auth;

class PaxMerchantProfileController extends Controller
{
    protected PaxMerchantProfileService $profileService;

    public function __construct(PaxMerchantProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function userProfile()
    {
        $user = $this->profileService->merchantProfile();

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'data' => $user
        ]);
    }

    public function updateMerchantAddress(PaxUpdateMerchantAddressRequest $request)
    {
        try {
            $data = $this->profileService->updateMerchantAddress($request->validated());

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Unable to update merchant address - ' . $e->getMessage()
            ], 500);
        }
    }
}
