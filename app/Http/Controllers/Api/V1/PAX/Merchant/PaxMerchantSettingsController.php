<?php

namespace App\Http\Controllers\Api\V1\PAX\Merchant;

use App\Http\Controllers\Controller;
use App\Services\Api\PAX\Merchant\PaxMerchantSettingService;

class PaxMerchantSettingsController extends Controller
{
    protected PaxMerchantSettingService $paxMerchantSettingService;

    public function __construct(PaxMerchantSettingService $paxMerchantSettingService)
    {
        $this->paxMerchantSettingService =  $paxMerchantSettingService;
    }

    /**
     * Get merchant settings
     */
    public function getMerchantSettings()
    {
        try {
            $data = $this->paxMerchantSettingService->fetchMerchantSettings();

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
