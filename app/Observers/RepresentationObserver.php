<?php

namespace App\Observers;

use App\Models\Representation;
use App\Services\Cyclos\CyclosUserService;
use Illuminate\Support\Facades\Log;

class RepresentationObserver
{
    public function updated(Representation $representation): void
    {
        if (!$representation->wasChanged('status')) {
            return;
        }

        if ($representation->status !== 'approved') {
            return;
        }

        $user = $representation->user;

        if (!$user) {
            Log::warning('No user found for representation', ['representation_id' => $representation->id]);
            return;
        }

        // Check if user already has Cyclos credentials
        if ($user->cyclos_user_id) {
            Log::info('User already has Cyclos credentials', [
                'user_id' => $user->id,
                'cyclos_user_id' => $user->cyclos_user_id
            ]);
            return;
        }

        $cyclosService = app(CyclosUserService::class);
        $response = $cyclosService->createUser($user);

        Log::info('Cyclos user API response', [
            'cyclos_response' => $response,
            'user_id' => $user->id
        ]);

        if (!$response) {
            Log::error('Failed to create Cyclos user', ['user_id' => $user->id]);
            return;
        }

        // Extract data from response
        $cyclosUserId = $response['user_id'] ?? $response['response']['user']['id'] ?? $response['response']['id'] ?? null;
        $cyclosUsername = $response['username'] ?? $response['response']['user']['username'] ?? $response['response']['username'] ?? $response['response']['user']['login'] ?? null;
        $group = $response['group'] ?? null;

        if (!$cyclosUserId) {
            Log::error('No Cyclos user ID in response', [
                'user_id' => $user->id,
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

        Log::info('Cyclos user linked successfully', [
            'user_id' => $user->id,
            'cyclos_user_id' => $cyclosUserId,
            'cyclos_username' => $cyclosUsername ?? 'NOT SET',
            'group' => $group
        ]);
    }
}