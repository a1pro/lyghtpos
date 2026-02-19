<?php

namespace App\Observers\Subadmin\Merchant;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    /**
     * Clear all merchant-related caches
     */
    protected function clearMerchantCaches(): void
    {
        $cacheKeys = [
            'merchants.list',
            'merchants.active',
            'merchants.pending',
            'merchants.statistics',
            // Add any other merchant-related cache keys
        ];

        foreach ($cacheKeys as $key) {
            if (Cache::has($key)) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Check if user is a merchant or merchant-related changes occurred
     */
    protected function shouldClearCache(User $user, array $changedFields = []): bool
    {
        if ($user->isMerchant()) {
            return true;
        }

        // Check if any merchant-related fields were changed
        $merchantFields = ['role_id', 'merchant_id', 'status', 'is_active'];
        foreach ($merchantFields as $field) {
            if (in_array($field, $changedFields)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        if ($this->shouldClearCache($user)) {
            $this->clearMerchantCaches();
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $changedFields = array_keys($user->getChanges());

        if ($this->shouldClearCache($user, $changedFields)) {
            $this->clearMerchantCaches();
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        if ($this->shouldClearCache($user)) {
            $this->clearMerchantCaches();
        }
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        if ($this->shouldClearCache($user)) {
            $this->clearMerchantCaches();
        }
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        if ($this->shouldClearCache($user)) {
            $this->clearMerchantCaches();
        }
    }
}
