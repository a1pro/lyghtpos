<?php

namespace App\Http\Controllers\Api\V1\CLOVER\Merchant;

use App\Http\Controllers\Controller;
use App\Services\Api\CLOVER\Merchant\CloverMerchantSettingService;

class CloverMerchantSettingsController extends Controller
{
    protected CloverMerchantSettingService $cloverMerchantSettingService;

    public function __construct(CloverMerchantSettingService $cloverMerchantSettingService)
    {
        $this->cloverMerchantSettingService =  $cloverMerchantSettingService;
    }

    /**
     * Get merchant settings
     */
    public function getMerchantSettings()
    {
        try {
            $data = $this->cloverMerchantSettingService->fetchMerchantSettings();

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Merchant settings retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request: ' . $e->getMessage()
            ], 500);
        }
    }
}
