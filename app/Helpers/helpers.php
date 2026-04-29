<?php

use App\Models\AppSetting;
use App\Models\Page;
use App\Models\Representation;
use App\Models\RepresentationReviewLog;
use App\Models\User;
use App\Models\Notification;
use App\Models\GovernanceLayer;
use App\Models\UserGovernanceLayer;
use App\Models\Role;
use App\Models\UserSubscription;
use Carbon\Carbon;
 

if (!function_exists('activeSegment')) {
    function activeSegment($name, $segment = 2, $class = 'active')
    {
        return request()->segment($segment) == $name ? $class : '';
    }
}

if (!function_exists('representation_status')) {
    function representation_status()
    {
        return Representation::where('user_id', auth()->id())->value('status');
    }
}

if (!function_exists('notifications')) {
    function notifications($limit = 5)
    {
        if (!auth()->check()) {
            return collect();
        }

        return \App\Models\Notification::where('user_id', auth()->id())
            ->latest()
            ->take($limit)
            ->get();
    }
}

if (!function_exists('payment_check')) {
    function payment_check()
    {
        $user = auth()->user();

        // Get the first representation
        $firstEntry = Representation::where('user_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$firstEntry) {
            return [
                'show_popup' => false,
                'status_code' => 0,
                'message' => 'No representation found',
                'pledge_value' => 0
            ];
        }

        // Check if all governance layers are approved
        $totalGovernanceLayers = UserGovernanceLayer::where('representation_id', $firstEntry->id)->count();
        $approvedGovernanceLayers = UserGovernanceLayer::where([
            'representation_id' => $firstEntry->id,
            'status' => 'approved'
        ])->count();

        // Check if all layers are approved
        $allLayersApproved = ($totalGovernanceLayers > 0 && $totalGovernanceLayers == $approvedGovernanceLayers);

        // Check payment status
        $paymentStatus = $firstEntry->payment_status ?? null;
        $paymentPendingOrCancel = in_array($paymentStatus, ['pending', 'cancel', 'cancelled', 'failed']);

        // Determine if popup should be shown
        $showPopup = ($allLayersApproved && $paymentPendingOrCancel);

        return [
            'show_popup' => $showPopup,
            'status_code' => $showPopup ? 1 : 0,
            'all_layers_approved' => $allLayersApproved,
            'approved_count' => $approvedGovernanceLayers,
            'total_layers' => $totalGovernanceLayers,
            'payment_status' => $paymentStatus,
            'representation_id' => $firstEntry->id,
            'pledge_value' => $firstEntry->pledge_value ?? 0 // Get pledge value from representation
        ];
    }

    /**
     * Encrypt a value
     */
    if (!function_exists('encryptVal')) {
        function encryptVal($value): string
        {
            return Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt a value
     */
    if (!function_exists('decryptVal')) {
        function decryptVal($encryptedValue): ?string
        {
            try {
                return Crypt::decryptString($encryptedValue);
            } catch (\Exception $e) {
                return null;
            }
        }
    }
}

if (!function_exists('setting')) {
    function setting($key, $group = null, $default = null)
    {
        $query = AppSetting::query();

        if ($group) {
            $query->where('group', $group);
        }

        $setting = $query->where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }
}

if (!function_exists('groupSettings')) {
    function groupSettings($group)
    {
        return AppSetting::where('group', $group)
            ->pluck('value', 'key')
            ->toArray();
    }


}
 

if (!function_exists('walletBalance')) {
    function walletBalance()
    {
        if (!auth()->check()) {
            return 0;
        }
        
        return auth()->user()->wallet_amount ?? 0;
    }
}

if (!function_exists('formatMoney')) {
    function formatMoney($amount)
    {
        $currency = config('app.currency_symbol', '$');
        return $currency . ' ' . number_format($amount, 2);
    }
}
// =======================Fetch All Roles====================

if(!function_exists('userRoles')){
    function userRoles()
    {
        return $role = Role::select('id','name')->get();

    }
}



// ================Check User Subscription=================

    if (!function_exists('userSubscription')) {
    /**
     * Check user's subscription status
     * 
     * @return string 'Active' or 'Expired'
     */
     function userSubscription()
    {   
        // Check if user is authenticated
        if (!Auth::check()) {
            return 'Expired';
        }
        
        $userId = Auth::id();
        $currentDate = Carbon::now();
        
        $userPlan = UserSubscription::where('user_id', $userId)
            ->where('status', 'active')
            ->first();
        
        // If no active subscription found, consider as expired
        if (!$userPlan) {
            return 'Expired';
        }
        
        $endDate = $userPlan->end_date;
        
        if ($endDate < $currentDate) {
            return 'Expired';
        } else {
            return 'Active';
        }
    }
}

if (!function_exists('getUserCurrentPlan')) {
    /**
     * Get user's current active subscription
     * 
     * @return UserSubscription|null
     */
    function getUserCurrentPlan()
    {
        if (!Auth::check()) {
            return null;
        }
        
        return UserSubscription::with('plan')
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->where('end_date', '>', Carbon::now())
            ->first();
    }
}

if (!function_exists('getAvailableUpgradePlans')) {
    /**
     * Get plans that are upgrades from current plan
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function getAvailableUpgradePlans()
    {
        $currentPlan = getUserCurrentPlan();
        
        $query = SubscriptionPlan::where('is_active', true);
        
        if ($currentPlan) {
            // Get plans with higher price or different features
            $query->where(function($q) use ($currentPlan) {
                $q->where('monthly_price', '>', $currentPlan->plan->monthly_price)
                  ->orWhere('yearly_price', '>', $currentPlan->plan->yearly_price);
            });
        }
        
        return $query->orderBy('monthly_price')->get();
    }
}


if (!function_exists('userSubscriptionStatus')) {
    function userSubscriptionStatus()
    {
        $subscription = getUserCurrentPlan();
        
        if (!$subscription) {
            return 'No Active Subscription';
        }
        
        if ($subscription->downgrade_to_plan_id) {
            return 'Active - Downgrade Scheduled';
        }
        
        return 'Active';
    }
}