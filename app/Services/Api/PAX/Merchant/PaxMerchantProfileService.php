<?php

namespace App\Services\Api\PAX\Merchant;

use App\Models\UserAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaxMerchantProfileService
{
    /**
     * Get merchant profile details
     */
    public function merchantProfile(): array
    {
        $user = Auth::user();

        return [
            'name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'address' => $user->address ?? null,
        ];
    }

    /**
     * Update merchant address details
     */
    public function updateMerchantAddress(array $data): array
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();

            if (!$user) {
                throw new \Exception("User not found");
            }

            $address = UserAddress::updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'postal_code' => $data['postal_code'],
                    'country' => $data['country'],
                ]
            );

            DB::commit();

            return [
                'address' => $address->only(['address', 'city', 'state', 'postal_code', 'country'])
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Update pax merchant address failed', [
                'user_id' => Auth::id(),
                'error' => $e->getTraceAsString(),
                'message' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
