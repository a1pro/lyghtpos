<?php

namespace App\Services\Cyclos;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\AppSetting;

class CyclosUserService
{
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $channel;
    protected $defaultGroup;
    protected $agreement;
    protected $adminUserId;
    protected $paymentTypeId;
    protected $currencyName;
    protected $currencySymbol;
    protected $currencyCode;
    protected $mockMode;
    protected $fallbackToMock;
    protected $useApiPassword;

    public function __construct()
    {
        // Load settings from database
        $this->loadSettings();
    }

    /**
     * Load Cyclos settings from database
     */
    private function loadSettings(): void
    {
        try {
            // Debug: Check what's actually in the config
            $cyclosConfig = config('services.cyclos');
            
            Log::info('Cyclos config structure', [
                'config_type' => gettype($cyclosConfig),
                'config_keys' => is_array($cyclosConfig) ? array_keys($cyclosConfig) : 'not array',
                'full_config' => $cyclosConfig
            ]);
            
            // Now try to get the base_url properly
            $baseUrlConfig = config('services.cyclos.base_url');
            
            Log::info('Base URL config', [
                'type' => gettype($baseUrlConfig),
                'value' => $baseUrlConfig
            ]);
            
            // Fix: Check if base_url is an array and extract the string value
            $baseUrl = '';
            if (is_array($baseUrlConfig)) {
                // If it's an array, try to get the first value or handle accordingly
                $baseUrl = reset($baseUrlConfig) ?: '';
                Log::warning('base_url was an array, extracted first value', [
                    'original' => $baseUrlConfig,
                    'extracted' => $baseUrl
                ]);
            } else {
                $baseUrl = $baseUrlConfig ?: '';
            }
            
            $this->baseUrl = rtrim($baseUrl, '/');
            $this->username = config('services.cyclos.username', '');
            $this->password = config('services.cyclos.password', '');
            $this->channel = config('services.cyclos.channel', 'webServices');
            $this->defaultGroup = config('services.cyclos.default_group', 'members');
            $this->agreement = config('services.cyclos.agreement', 'default-agreement');
            $this->adminUserId = config('services.cyclos.admin_user_id');
            $this->paymentTypeId = config('services.cyclos.payment_type_id');
            $this->currencyName = config('services.cyclos.currency_name', 'TradeTokens');
            $this->currencySymbol = config('services.cyclos.currency_symbol', 'TT');
            $this->currencyCode = config('services.cyclos.currency_code', 'TT');
            $this->mockMode = config('services.cyclos.mock_mode', false);
            $this->fallbackToMock = config('services.cyclos.fallback_to_mock', true);
            $this->useApiPassword = config('services.cyclos.use_api_password', true);
            
            Log::info('Cyclos settings loaded from config', [
                'base_url' => $this->baseUrl,
                'has_username' => !empty($this->username),
                'has_password' => !empty($this->password),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to load Cyclos settings', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if Cyclos is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->username) && !empty($this->password);
    }

    /**
     * Create a Cyclos user for the given user.
     *
     * @param User $user
     * @return array|null
     */
    public function createUser(User $user): ?array
    {
        // Check if Cyclos is configured
        if (!$this->isConfigured()) {
            Log::warning('Cyclos not configured, skipping user creation', [
                'user_id' => $user->id
            ]);
            return null;
        }

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
                $this->agreement
            ],
            'asMember' => false,
        ];

        Log::info('Cyclos user creation request', [
            'user_id' => $user->id,
            'username' => $username,
            'password' => $password,
            'group' => $group,
            'url' => $this->baseUrl . '/api/users',
            'has_admin_user_id' => !empty($this->adminUserId)
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
            
            // Extract data from the nested structure based on actual API response
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
                return $this->defaultGroup;
            default:
                return $this->defaultGroup;
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
        if (strlen($baseUsername) > 45) {
            $baseUsername = substr($baseUsername, 0, 45);
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
        // Check if Cyclos is configured
        if (!$this->isConfigured()) {
            Log::warning('Cyclos not configured, cannot get user');
            return null;
        }

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
        // Check if Cyclos is configured
        if (!$this->isConfigured()) {
            Log::warning('Cyclos not configured, cannot check user existence');
            return false;
        }

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


      /**
     * Get admin user ID
     *
     * @return string|null
     */
    public function getAdminUserId(): ?string
    {
        return $this->adminUserId;
    }

    /**
     * Get payment type ID
     *
     * @return string|null
     */
    public function getPaymentTypeId(): ?string
    {
        return $this->paymentTypeId;
    }

    /**
     * Get currency name
     *
     * @return string
     */
    public function getCurrencyName(): string
    {
        return $this->currencyName;
    }

    /**
     * Get currency symbol
     *
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    /**
     * Reload settings (useful after updating settings)
     */
    public function reloadSettings(): void
    {
        $this->loadSettings();
        Log::info('Cyclos settings reloaded', [
            'is_configured' => $this->isConfigured()
        ]);
    }
}
