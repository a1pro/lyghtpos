<?php

namespace App\Http\Controllers\Api\V1\PAX\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PAX\Merchant\UpdateCardSurchargeRequest;
use App\Services\Api\PAX\Merchant\PaxMerchantSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    /**
     * Update card surcharge percentage
     */
    public function updateCardSurcharge(UpdateCardSurchargeRequest $request)
    {
        try {
            $result = $this->paxMerchantSettingService->updateCardSurcharge(
                $request->card_surcharge_percentage
            );

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Card surcharge percentage updated successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update card surcharge', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request: ' . $e->getMessage()
            ], 500);
        }
    }
}
