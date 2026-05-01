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
}