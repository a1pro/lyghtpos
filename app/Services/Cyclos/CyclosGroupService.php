<?php

namespace App\Services\Cyclos;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CyclosGroupService
{
    protected $baseUrl;

    protected $username;

    protected $password;

    protected $channel;

    protected $mockMode;

    protected $fallbackToMock;

    // Cache for groups to avoid repeated API calls
    protected $groupsCache = [];

    protected $cacheDuration = 3600; // 1 hour

    public function __construct()
    {
        $this->loadSettings();
    }

    /**
     * Load Cyclos settings from config
     */
    private function loadSettings(): void
    {
        try {
            $baseUrlConfig = config('services.cyclos.base_url');

            // Handle if base_url is an array
            $baseUrl = '';
            if (is_array($baseUrlConfig)) {
                $baseUrl = reset($baseUrlConfig) ?: '';
            } else {
                $baseUrl = $baseUrlConfig ?: '';
            }

            $this->baseUrl = rtrim($baseUrl, '/');
            $this->username = config('services.cyclos.username', '');
            $this->password = config('services.cyclos.password', '');
            $this->channel = config('services.cyclos.channel', 'webServices');
            $this->mockMode = config('services.cyclos.mock_mode', false);
            $this->fallbackToMock = config('services.cyclos.fallback_to_mock', true);

            Log::info('CyclosGroupService settings loaded', [
                'base_url' => $this->baseUrl,
                'has_username' => ! empty($this->username),
                'has_password' => ! empty($this->password),
                'mock_mode' => $this->mockMode,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load CyclosGroupService settings', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if Cyclos is configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->username) && ! empty($this->password);
    }

    /**
     * Get HTTP client with authentication
     */
    protected function getHttpClient()
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Channel' => $this->channel,
            ]);
    }

    /**
     * Get current group information for a Cyclos user
     *
     * @return array|null Returns group info with 'current_group', 'possible_groups', 'history'
     */
    public function getUserGroup(string $cyclosUserId): ?array
    {
        if (! $this->isConfigured()) {
            Log::warning('Cyclos not configured, cannot get user group');

            return $this->getMockUserGroup($cyclosUserId);
        }

        try {
            $response = $this->getHttpClient()
                ->get($this->baseUrl.'/api/'.$cyclosUserId.'/group');

            if ($response->failed()) {
                Log::error('Failed to fetch Cyclos user group', [
                    'cyclos_user_id' => $cyclosUserId,
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);

                if ($this->fallbackToMock) {
                    return $this->getMockUserGroup($cyclosUserId);
                }

                return null;
            }

            $data = $response->json();

            Log::info('Retrieved user group from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'current_group' => $data['group']['name'] ?? 'unknown',
                'possible_groups_count' => count($data['possibleNewGroups'] ?? []),
            ]);

            return [
                'current_group' => $data['group'] ?? null,
                'possible_groups' => $data['possibleNewGroups'] ?? [],
                'history' => $data['history'] ?? [],
                'status' => $data['status'] ?? 'active',
                'raw_response' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('Exception fetching Cyclos user group', [
                'cyclos_user_id' => $cyclosUserId,
                'error' => $e->getMessage(),
            ]);

            if ($this->fallbackToMock) {
                return $this->getMockUserGroup($cyclosUserId);
            }

            return null;
        }
    }

    /**
     * Change user's group in Cyclos
     *
     * @param  string  $newGroupId  Can be either group ID or internal name
     * @param  string|null  $comment  Optional comment for the group change
     */
    public function changeUserGroup(string $cyclosUserId, string $newGroupId, ?string $comment = null): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('Cyclos not configured, cannot change user group');

            return $this->mockChangeUserGroup($cyclosUserId, $newGroupId, $comment);
        }

        try {
            $payload = [
                'group' => $newGroupId,  // Can be either ID or internal name
            ];

            if ($comment) {
                $payload['comment'] = $comment;
            }

            Log::info('Changing Cyclos user group', [
                'cyclos_user_id' => $cyclosUserId,
                'new_group' => $newGroupId,
                'comment' => $comment,
            ]);

            $response = $this->getHttpClient()
                ->post($this->baseUrl.'/api/'.$cyclosUserId.'/group', $payload);

            if ($response->failed()) {
                Log::error('Failed to change Cyclos user group', [
                    'cyclos_user_id' => $cyclosUserId,
                    'new_group' => $newGroupId,
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);

                return false;
            }

            Log::info('Successfully changed Cyclos user group', [
                'cyclos_user_id' => $cyclosUserId,
                'new_group' => $newGroupId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Exception changing Cyclos user group', [
                'cyclos_user_id' => $cyclosUserId,
                'new_group' => $newGroupId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get all available groups from Cyclos (by fetching a sample user's possible groups)
     * This is a workaround since there's no direct "list all groups" API endpoint
     *
     * @param  string|null  $sampleUserId  Optional sample user ID to fetch groups from
     */
    public function getAllAvailableGroups(?string $sampleUserId = null): array
    {
        // Check cache first
        $cacheKey = 'cyclos_all_groups';
        if (isset($this->groupsCache[$cacheKey])) {
            return $this->groupsCache[$cacheKey];
        }

        if (! $this->isConfigured()) {
            Log::warning('Cyclos not configured, returning mock groups');

            return $this->getMockAllGroups();
        }

        try {
            // If no sample user provided, we need to find one
            if (! $sampleUserId) {
                $sampleUserId = $this->findAnyCyclosUser();
            }

            if (! $sampleUserId) {
                Log::warning('No Cyclos user found to fetch groups from');

                return $this->getMockAllGroups();
            }

            $userGroupData = $this->getUserGroup($sampleUserId);

            if (! $userGroupData || empty($userGroupData['possible_groups'])) {
                Log::warning('No possible groups found for user', ['cyclos_user_id' => $sampleUserId]);

                return $this->getMockAllGroups();
            }

            // Also include the current group if not already in possible groups
            $allGroups = $userGroupData['possible_groups'];
            $currentGroup = $userGroupData['current_group'];

            if ($currentGroup && ! $this->groupExistsInList($currentGroup, $allGroups)) {
                array_unshift($allGroups, $currentGroup);
            }

            // Cache the result
            $this->groupsCache[$cacheKey] = $allGroups;

            Log::info('Retrieved all available groups', [
                'total_groups' => count($allGroups),
                'source_user' => $sampleUserId,
            ]);

            return $allGroups;

        } catch (\Exception $e) {
            Log::error('Exception fetching all groups', [
                'error' => $e->getMessage(),
            ]);

            return $this->getMockAllGroups();
        }
    }

    /**
     * Find any Cyclos user to use as a sample for fetching groups
     */
    protected function findAnyCyclosUser(): ?string
    {
        try {
            // Try to find a user from your database that has cyclos_user_id
            $userWithCyclosId = User::whereNotNull('cyclos_user_id')
                ->where('cyclos_user_id', '!=', '')
                ->first();

            if ($userWithCyclosId && $userWithCyclosId->cyclos_user_id) {
                return $userWithCyclosId->cyclos_user_id;
            }

            // Alternative: Try to fetch users from Cyclos API
            $response = $this->getHttpClient()
                ->get($this->baseUrl.'/api/users', [
                    'pageSize' => 1,
                    'fields' => 'id',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data['pageItems']) && isset($data['pageItems'][0]['id'])) {
                    return $data['pageItems'][0]['id'];
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Exception finding any Cyclos user', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if a group exists in a list
     */
    protected function groupExistsInList(array $group, array $groupList): bool
    {
        foreach ($groupList as $existingGroup) {
            if (isset($existingGroup['id']) && $existingGroup['id'] === $group['id']) {
                return true;
            }
            if (isset($existingGroup['internalName']) && isset($group['internalName'])
                && $existingGroup['internalName'] === $group['internalName']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sync user group between local database and Cyclos
     *
     * @return array|null Returns current group info after sync
     */
    public function syncUserGroup(User $user): ?array
    {
        if (! $user->cyclos_user_id) {
            Log::warning('User has no Cyclos ID, cannot sync group', ['user_id' => $user->id]);

            return null;
        }

        $cyclosGroupData = $this->getUserGroup($user->cyclos_user_id);

        if (! $cyclosGroupData) {
            return null;
        }

        // Update local user with Cyclos group info if needed
        $currentGroupName = $cyclosGroupData['current_group']['name'] ?? null;
        $currentGroupInternalName = $cyclosGroupData['current_group']['internalName'] ?? null;

        // Store group info in user if you have fields for it
        // You might want to add 'cyclos_group_name' and 'cyclos_group_internal_name' to users table
        if (property_exists($user, 'cyclos_group_name')) {
            $user->cyclos_group_name = $currentGroupName;
            $user->cyclos_group_internal_name = $currentGroupInternalName;
            $user->save();
        }

        return $cyclosGroupData;
    }

    /**
     * Get mock user group data for testing
     */
    protected function getMockUserGroup(string $cyclosUserId): array
    {
        return [
            'current_group' => [
                'id' => 'mock_group_001',
                'name' => 'User',
                'internalName' => 'members',
                'kind' => 'memberGroup',
            ],
            'possible_groups' => [
                [
                    'id' => 'mock_group_001',
                    'name' => 'User',
                    'internalName' => 'members',
                    'kind' => 'memberGroup',
                ],
                [
                    'id' => 'mock_group_002',
                    'name' => 'Merchants',
                    'internalName' => 'merchants',
                    'kind' => 'memberGroup',
                ],
                [
                    'id' => 'mock_group_003',
                    'name' => 'Advocates',
                    'internalName' => 'advocates',
                    'kind' => 'brokerGroup',
                ],
            ],
            'history' => [],
            'status' => 'active',
            'is_mock' => true,
        ];
    }

    /**
     * Get mock all groups for testing
     */
    protected function getMockAllGroups(): array
    {
        return [
            [
                'id' => 'mock_group_001',
                'name' => 'User',
                'internalName' => 'members',
                'kind' => 'memberGroup',
            ],
            [
                'id' => 'mock_group_002',
                'name' => 'Merchants',
                'internalName' => 'merchants',
                'kind' => 'memberGroup',
            ],
            [
                'id' => 'mock_group_003',
                'name' => 'Advocates',
                'internalName' => 'advocates',
                'kind' => 'brokerGroup',
            ],
            [
                'id' => 'mock_group_004',
                'name' => 'BarterAssist Merchants',
                'internalName' => 'barterassist',
                'kind' => 'memberGroup',
            ],
            [
                'id' => 'mock_group_005',
                'name' => 'FavorBanc Organization',
                'internalName' => 'favorbanc_organization',
                'kind' => 'memberGroup',
            ],
        ];
    }

    /**
     * Mock group change for testing
     */
    protected function mockChangeUserGroup(string $cyclosUserId, string $newGroupId, ?string $comment = null): bool
    {
        Log::info('MOCK: Changing Cyclos user group', [
            'cyclos_user_id' => $cyclosUserId,
            'new_group' => $newGroupId,
            'comment' => $comment,
        ]);

        return true;
    }

    /**
     * Clear groups cache
     */
    public function clearCache(): void
    {
        $this->groupsCache = [];
        Log::info('CyclosGroupService cache cleared');
    }

    /**
     * Reload settings from database
     */
    public function reloadSettings(): void
    {
        $this->clearCache();
        $this->loadSettings();
    }
}
