<?php

namespace App\Services\Merchant;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Hash;

class MerchantProfileService
{
    /**
     * Get merchant profile data
     */
    public function getProfileData(User $user)
    {
        return [
            'user' => $user,
            'merchant' => $user->merchant,
        ];
    }

    /**
     * Update merchant profile
     */
    public function updateProfile(User $user, array $data)
    {
        try {
            $user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? $user->phone,
            ]);

            return [
                'success' => true,
                'message' => 'Profile updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user address
     */
    public function getUserAddress(User $user)
    {
        try {
            $address = UserAddress::where('user_id', $user->id)->first();
            return $address;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update merchant settings
     */
    public function updateSettings(User $user, array $data)
    {
        try {
            $merchant = $user->merchant;

            if (!$merchant) {
                return [
                    'success' => false,
                    'message' => 'Merchant record not found'
                ];
            }

            // Update VAT tax percentage
            if (isset($data['vat_tax_percentage'])) {
                $merchant->vat_tax_percentage = $data['vat_tax_percentage'];
            }

            // Update POS payment types
            if (isset($data['pos_payment_types'])) {
                // If it's already an array, encode to JSON
                if (is_array($data['pos_payment_types'])) {
                    $merchant->pos_payment_types = json_encode($data['pos_payment_types']);
                } else {
                    $merchant->pos_payment_types = $data['pos_payment_types'];
                }
            }

             if (isset($data['handpoint_api_key'])) {
                $merchant->handpoint_api_key = $data['handpoint_api_key'];
            }

            $merchant->save();

            return [
                'success' => true,
                'message' => 'Settings updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ];
        }
    }
}
