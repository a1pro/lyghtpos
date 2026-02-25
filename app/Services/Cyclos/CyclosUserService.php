<?php

namespace App\Services\Cyclos;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class CyclosUserService
{
    /**
     * Create a Cyclos user for the given user.
     *
     * @param User $user
     * @return array|null
     */
    public function createUser(User $user): ?array
    {
        if ($user->role_id == 1) {
            Log::info("Skipping Cyclos API for admin user ID {$user->id}");
            return null;
        }

        if ($user->role_id == 5) {
            Log::info("Skipping Cyclos API for subadmin user ID {$user->id}");
            return null;
        }

        // Determine group based on role
        switch ($user->role_id) {
            case 2: // Merchant
                $group = 'simplbarter';
                break;
            case 6: // Trade Member
                $group = 'simplbarter';
                break;
            case 7: // Organization
                $group = 'simplbarter';
                break;
            case 8: // Favor Member
                $group = 'members';
                break;
            default:
                $group = config('services.cyclos.default_group');
                break;
        }

        // Prepare username
        $cleanFirstName = preg_replace('/\s+/', '', $user->first_name);
        $cleanLastName = preg_replace('/\s+/', '', $user->last_name);
        $username = strtolower($cleanFirstName . $cleanLastName . $user->id);

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withBasicAuth(
            config('services.cyclos.username'),
            config('services.cyclos.password')
        )
            ->acceptJson()
            ->post(config('services.cyclos.base_url') . '/api/users', [
                'name' => $user->first_name . ' ' . $user->last_name,
                'username' => $username,
                'email' => $user->email,
                'group' => $group,
                'passwords' => [
                    [
                        'type' => 'login',
                        'value' => 'forGod!2248',
                        'checkConfirmation' => true,
                        'confirmationValue' => 'forGod!2248',
                        'forceChange' => false,
                    ]
                ],
                'skipActivationEmail' => true,
                'acceptAgreement' => true,
                'acceptAgreements' => [
                    config('services.cyclos.agreement')
                ],
                'asMember' => false,
                'userAgentId' => 'web',
            ]);

        if ($response->failed()) {
            Log::error('Cyclos user creation failed', [
                'user_id' => $user->id,
                'response' => $response->json(),
            ]);
            return null;
        }

        return [
            'response' => $response->json(),
            'group' => $group,
        ];
    }
}
