<?php

namespace App\Services\Api\PAX\Merchant;

use App\Models\Merchant;
use Auth;

class PaxMerchantSettingService
{
    /**
     * Return merchant's settings
     */
    public function fetchMerchantSettings()
    {
        // Default values
        $defaultPaymentTypes = ['card', 'tokens', 'hybrid', 'cash', 'pos_mobile'];
        $defaultVatTax = 10.00;

        $user = Auth::user();
        $merchant = Merchant::select('vat_tax_percentage', 'pos_payment_types', 'handpoint_api_key')
            ->where("user_id", $user->id)
            ->first();

        if (!$merchant) {
            throw new \Exception("Merchant cannot be found!");
        }

        $vatTaxPercentage = !is_null($merchant->vat_tax_percentage)
            ? floatval($merchant->vat_tax_percentage)
            : $defaultVatTax;

        $paymentTypes = $defaultPaymentTypes;

        if (!is_null($merchant->pos_payment_types)) {
            $decoded = json_decode($merchant->pos_payment_types, true);

            if (is_array($decoded) && !empty($decoded)) {
                $paymentTypes = $decoded;
            }
        }

        return [
            'vat_tax_percentage' => $vatTaxPercentage,
            'pos_payment_types' => $paymentTypes,
            'handpoint_api_key' => $merchant->handpoint_api_key ?? null
        ];
    }
}
