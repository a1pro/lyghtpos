<?php

namespace App\Services\Cyclos;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Str;

class CyclosUserService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $channel;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.cyclos.base_url'), '/');
        $this->username = config('services.cyclos.username');
        $this->password = config('services.cyclos.password');
        $this->channel = config('services.cyclos.channel', 'webServices');
    }

    /**
     * Create a Cyclos user for the given user.
     *
     * @param User $user
     * @return array|null
     */
    public function createUser(User $user): ?array
    {
        // Skip for admin and subadmin
        if (in_array($user->role_id, [1, 5])) {
            Log::info("Skipping Cyclos API for user ID {$user->id} with role_id {$user->role_id}");
            return null;
        }

        // Determine group based on role
        $group = $this->determineGroup($user->role_id);

        // Prepare username
        $username = $this->generateUsername($user);
        $password = Str::random(10);

        // Prepare the request data
        $requestData = [
            'name' => trim($user->first_name . ' ' . $user->last_name),
            'username' => $username,
            'email' => $user->email,
            'group' => $group,
            'passwords' => [
                [
                    'type' => 'login',
                    'value' => $password,
                    'checkConfirmation' => true,
                    'confirmationValue' => $password,
                    'forceChange' => false,
                ]
            ],
            'skipActivationEmail' => true,
            'acceptAgreement' => true,
            'acceptAgreements' => [
                config('services.cyclos.agreement', 'default-agreement')
            ],
            'asMember' => false,
        ];

        Log::info('Cyclos user creation request', [
            'user_id' => $user->id,
            'username' => $username,
            'password' => $password,
            'group' => $group,
            'url' => $this->baseUrl . '/api/users'
        ]);

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->post($this->baseUrl . '/api/users', $requestData);

            // Log the raw response for debugging
            Log::info('Cyclos API raw response', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->failed()) {
                $errorData = $response->json();

                Log::error('Cyclos user creation failed', [
                    'user_id' => $user->id,
                    'role_id' => $user->role_id,
                    'status' => $response->status(),
                    'error_code' => $errorData['code'] ?? 'UNKNOWN',
                    'error_message' => $errorData['message'] ?? 'No message',
                    'error_details' => $errorData,
                    'channel_used' => $this->channel,
                    'group_used' => $group,
                ]);

                return null;
            }

            $responseData = $response->json();

            // FIXED: Extract data from the nested structure based on actual API response
            // The user data is inside the 'user' object
            $userData = $responseData['user'] ?? null;

            if (!$userData) {
                Log::error('Cyclos response missing user object', [
                    'user_id' => $user->id,
                    'response' => $responseData
                ]);
                return null;
            }

            // Extract user ID from the nested user object
            $cyclosUserId = $userData['id'] ?? null;

            // Extract username from principals array
            $cyclosUsername = $username; // Default to our generated username
            if (isset($responseData['principals']) && is_array($responseData['principals'])) {
                foreach ($responseData['principals'] as $principal) {
                    if (isset($principal['type']['internalName']) && $principal['type']['internalName'] === 'username') {
                        $cyclosUsername = $principal['value'] ?? $username;
                        break;
                    }
                }
            }

            // Get status
            $status = $responseData['status'] ?? 'active';

            // Log success with extracted data
            Log::info('Cyclos user created successfully', [
                'user_id' => $user->id,
                'role_id' => $user->role_id,
                'cyclos_user_id' => $cyclosUserId,
                'cyclos_username' => $cyclosUsername,
                'cyclos_password' => $password,
                'group' => $group,
                'status' => $status,
            ]);

            // Check if we got a valid user ID
            if (!$cyclosUserId) {
                Log::error('Cyclos response missing user ID', [
                    'user_id' => $user->id,
                    'user_data' => $userData,
                    'full_response' => $responseData
                ]);
                return null;
            }

            return [
                'response' => $responseData,
                'group' => $group,
                'username' => $cyclosUsername,
                'password' => $password,
                'user_id' => $cyclosUserId,
                'status' => $status,
            ];
        } catch (\Exception $e) {
            Log::error('Exception in Cyclos user creation', [
                'user_id' => $user->id,
                'role_id' => $user->role_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Determine the Cyclos group based on user role
     *
     * @param int $roleId
     * @return string
     */
    protected function determineGroup(int $roleId): string
    {
        // All non-admin users go to 'members' group
        switch ($roleId) {
            case 2: // Merchant
            case 6: // Trade Member
            case 7: // Organization
            case 8: // Favor Member
                return config('services.cyclos.default_group', 'members');
            default:
                return config('services.cyclos.default_group', 'members');
        }
    }

    /**
     * Generate a unique username for Cyclos
     *
     * @param User $user
     * @return string
     */
    protected function generateUsername(User $user): string
    {
        // Remove spaces and special characters
        $cleanFirstName = preg_replace('/[^a-zA-Z0-9]/', '', $user->first_name);
        $cleanLastName = preg_replace('/[^a-zA-Z0-9]/', '', $user->last_name);

        // Create base username
        $baseUsername = strtolower($cleanFirstName . $cleanLastName);

        // Ensure it's not too long (Cyclos typically has limits)
        if (strlen($baseUsername) > 20) {
            $baseUsername = substr($baseUsername, 0, 20);
        }

        // Add user ID to ensure uniqueness
        return $baseUsername . $user->id;
    }

    /**
     * Get user details from Cyclos
     *
     * @param string $cyclosUserId
     * @return array|null
     */
    public function getUser(string $cyclosUserId): ?array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->get($this->baseUrl . '/api/users/' . $cyclosUserId);

            if ($response->failed()) {
                Log::error('Failed to fetch Cyclos user', [
                    'cyclos_user_id' => $cyclosUserId,
                    'status' => $response->status()
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Exception fetching Cyclos user', [
                'cyclos_user_id' => $cyclosUserId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if a user exists in Cyclos by email
     *
     * @param string $email
     * @return bool
     */
    public function userExists(string $email): bool
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->get($this->baseUrl . '/api/users', [
                    'fields' => 'email',
                    'email' => $email,
                    'pageSize' => 1
                ]);

            if ($response->failed()) {
                return false;
            }

            $data = $response->json();
            return !empty($data['pageItems'] ?? []);
        } catch (\Exception $e) {
            Log::error('Exception checking Cyclos user existence', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * UPDATE User Cyclos password
     */
    public function updateUserPassword($cyclosUserId, $newPassword): bool
    {
        $endpoint = $this->baseUrl . "/api/{$cyclosUserId}/passwords/login/change";

        try {
            $payload = [
                "newPassword" => $newPassword,
                "forceChange" => false
            ];

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->post($endpoint, $payload);

            if ($response->failed()) {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Exception updating Cyclos user password', [
                'cyclos_user_id' => $cyclosUserId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
