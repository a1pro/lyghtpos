<?php

namespace App\Http\Controllers\Api\V1\POS\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class PosSettingsController extends Controller
{
    /**
     * Get custom token charge setting
     *
     * @return JsonResponse
     */
    public function getCustomTokenCharge(): JsonResponse
    {
        $setting = Setting::where('key', 'custom_token_charge')->first();

        $charge = $setting ? $setting->value : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'custom_token_charge' => (float) $charge
            ]
        ]);
    }
}
