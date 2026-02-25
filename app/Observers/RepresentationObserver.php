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

        if (!$user || $user->cyclos_user_id) {
            return;
        }

        $cyclosService = app(CyclosUserService::class);
        $response = $cyclosService->createUser($user);

        Log::info('Cyclos user API response',[
            'cyclos_response' => $response,
            'user_id' => $response['response']['user']['id'],
        ]);

        if (!$response || empty($response['response']['user']['id'])) {
            return;
        }

        $user->update([
            'cyclos_user_id' => $response['response']['user']['id'],
            'cyclos_group' => $response['group'],
        ]);
    }
}
