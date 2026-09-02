<?php

namespace App\Http\Controllers\Api\V1\POS\Customer;

use App\Http\Controllers\Controller;
use App\Services\Api\POS\Customer\PosCustomerMerchantSettingsService;

class PosCustomerMerchantSettingsController extends Controller
{
    protected PosCustomerMerchantSettingsService $customerMerchantSettingsService;

    public function __construct(PosCustomerMerchantSettingsService $customerMerchantSettingsService)
    {
        $this->customerMerchantSettingsService = $customerMerchantSettingsService;
    }

    public function getCustomerMerchantSettings()
    {
        try {
            $response = $this->customerMerchantSettingsService->getCustomerMerchantSettings();

            if (!$response['success']) {
                return response()->json($response, 400);
            }
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('Error retrieving customer merchant settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving merchant settings.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
