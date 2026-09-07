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
        $defaultCardSurcharge = 0;

        $user = Auth::user();
        $merchant = Merchant::select('vat_tax_percentage', 'card_surcharge_percentage', 'pos_payment_types', 'handpoint_api_key')
            ->where("user_id", $user->id)
            ->first();

        if (!$merchant) {
            throw new \Exception("Merchant cannot be found!");
        }

        $vatTaxPercentage = !is_null($merchant->vat_tax_percentage)
            ? number_format((float)$merchant->vat_tax_percentage, 2, '.', '')
            : $defaultVatTax;

        $cardSurchargePercentage = !is_null($merchant->card_surcharge_percentage)
            ? number_format((float)$merchant->card_surcharge_percentage, 2, '.', '')
            : $defaultCardSurcharge;

        $paymentTypes = $defaultPaymentTypes;

        if (!is_null($merchant->pos_payment_types)) {
            $decoded = json_decode($merchant->pos_payment_types, true);

            if (is_array($decoded) && !empty($decoded)) {
                $paymentTypes = $decoded;
            }
        }

        return [
            'vat_tax_percentage' => round($vatTaxPercentage, 2),
            'card_surcharge_percentage' => round($cardSurchargePercentage, 2),
            'pos_payment_types' => $paymentTypes,
            'handpoint_api_key' => $merchant->handpoint_api_key ?? null
        ];
    }

    /**
     * Update card surcharge percentage
     */
    public function updateCardSurcharge($cardSurchargePercentage)
    {
        $user = Auth::user();
        $merchant = Merchant::where("user_id", $user->id)->first();

        if (!$merchant) {
            throw new \Exception("Merchant cannot be found!");
        }

        // Update only the card_surcharge_percentage field
        $merchant->card_surcharge_percentage = number_format((float)$cardSurchargePercentage, 2, '.', '');
        $merchant->save();

        return [
            'card_surcharge_percentage' => number_format((float)$merchant->card_surcharge_percentage, 2, '.', '')
        ];
    }
}
