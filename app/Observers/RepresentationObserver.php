<?php

namespace App\Observers;

use App\Models\Representation;
use App\Models\User;
use App\Services\Cyclos\CyclosUserService;
use Illuminate\Support\Facades\Log;

class RepresentationObserver
{
    public function updated(Representation $representation): void
    {
        // Only proceed if status was changed to approved
        if (!$representation->wasChanged('status') || $representation->status !== 'approved') {
            return;
        }

        $user = $representation->user;

        if (!$user) {
            Log::warning('No user found for representation', ['representation_id' => $representation->id]);
            return;
        }

        // Skip if user already has Cyclos credentials
        if ($user->cyclos_user_id) {
            Log::info('User already has Cyclos credentials', [
                'user_id' => $user->id,
                'cyclos_user_id' => $user->cyclos_user_id
            ]);
            return;
        }

        // FAVOR_MEMBER (role_id = 8) should already have Cyclos account created at registration
        // So if we're here and it's a favor member, something is wrong
        if ($user->role_id == 8) { // Favor Member
            Log::warning('Favor member reached representation approval without Cyclos account', [
                'user_id' => $user->id
            ]);
            // Still try to create it as a fallback
            $this->createCyclosUser($user);
            return;
        }

        // For all other roles (including TRADE_MERCHANT), create Cyclos account on approval
        $this->createCyclosUser($user);
    }

    /**
     * Create Cyclos user and update user record
     */
    protected function createCyclosUser(User $user): void
    {
        $cyclosService = app(CyclosUserService::class);
        $response = $cyclosService->createUser($user);

        Log::info('Cyclos user API response for representation approval', [
            'response' => $response,
            'user_id' => $user->id,
            'role_id' => $user->role_id
        ]);

        if (!$response) {
            Log::error('Failed to create Cyclos user', [
                'user_id' => $user->id,
                'role_id' => $user->role_id
            ]);
            return;
        }

        // FIXED: Simplified extraction - the service now returns clean data
        $cyclosUserId = $response['user_id'] ?? null;
        $cyclosUsername = $response['username'] ?? null;
        $group = $response['group'] ?? null;

        if (!$cyclosUserId) {
            Log::error('No Cyclos user ID in response', [
                'user_id' => $user->id,
                'role_id' => $user->role_id,
                'response' => $response
            ]);
            return;
        }

        // Prepare update data
        $updateData = [
            'cyclos_user_id' => $cyclosUserId,
            'cyclos_group' => $group,
        ];

        // Add cyclos_username if available
        if ($cyclosUsername) {
            $updateData['cyclos_username'] = $cyclosUsername;
        }

        // Update the user
        $user->update($updateData);

        Log::info('Cyclos user linked successfully after representation approval', [
            'user_id' => $user->id,
            'role_id' => $user->role_id,
            'cyclos_user_id' => $cyclosUserId,
            'cyclos_username' => $cyclosUsername ?? 'NOT SET',
            'group' => $group
        ]);
    }
}