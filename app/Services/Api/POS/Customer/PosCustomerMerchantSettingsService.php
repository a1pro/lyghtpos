<?php

namespace App\Services\Api\POS\Customer;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PosCustomerMerchantSettingsService
{
    /**
     * Get the merchant settings for the authenticated customer
     * 
     * @return array
     */
    public function getCustomerMerchantSettings(): array
    {
        try {
            $user = Auth::user();

            // Check if user is authenticated
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not authenticated',
                ];
            }

            // Check if user is a customer
            if (!$user->isCustomer()) {
                return [
                    'success' => false,
                    'message' => 'User is not a customer',
                ];
            }

            // Check if user was referred by a merchant
            if (!$user->isReferredByMerchant()) {
                return [
                    'success' => false,
                    'message' => 'Customer is not referred by any merchant',
                    'data' => null,
                ];
            }

            // Load the merchant relationship with its user
            $merchant = $user->referredByMerchant()->with('user')->first();

            if (!$merchant) {
                return [
                    'success' => false,
                    'message' => 'Merchant details not found',
                    'data' => null,
                ];
            }

            // Prepare settings response
            $settings = [
                'success' => true,
                'message' => 'Merchant settings retrieved successfully',
                'data' => [
                    'merchant' => [
                        'id' => $merchant->id,
                        'user_id' => $merchant->user_id,
                        'merchant_code' => $merchant->merchant_id,
                        'first_name' => $merchant->user->first_name ?? null,
                        'last_name' => $merchant->user->last_name ?? null,
                        'email' => $merchant->user->email ?? null,
                        'vat_tax_percentage' => (float) ($merchant->vat_tax_percentage ?? 0),
                        'card_surcharge_percentage' => (float) ($merchant->card_surcharge_percentage ?? 0),
                    ]
                ]
            ];

            Log::info('Customer merchant settings retrieved', [
                'customer_id' => $user->id,
                'merchant_id' => $merchant->id,
            ]);

            return $settings;
        } catch (\Exception $e) {
            Log::error('Error fetching customer merchant settings', [
                'user_id' => Auth::id() ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve merchant settings: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }
}
