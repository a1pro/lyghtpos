<?php

namespace App\Http\Controllers\Api\V1\CLOVER\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CLOVER\Auth\CloverUpdateMerchantAddressRequest;
use App\Services\Api\CLOVER\Merchant\CloverMerchantProfileService;
use Illuminate\Support\Facades\Auth;

class CloverMerchantProfileController extends Controller
{
    protected CloverMerchantProfileService $profileService;

    public function __construct(CloverMerchantProfileService $profileService)
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

    public function updateMerchantAddress(CloverUpdateMerchantAddressRequest $request)
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
