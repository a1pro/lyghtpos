<?php
namespace App\Services\Merchant;

use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use App\Models\MerchantProductCount;
use App\Models\User;

class MerchantPlansConditionService
{
    
    public function canAddProducts($userId, $count = 1)
    {
        // Get user's active subscription
        try{
           
       $activeSubscription = UserSubscription::with('plan')->where('user_id', $userId)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->latest()
            ->first();
  
          $planId = $activeSubscription['plan_id'];
        if (!$activeSubscription) {
            return [
                'success' => false,
                'can_add' => false,  // Add this for consistency
                'message' => 'No active subscription found'
            ];
        }

       
        $productLimit = ($activeSubscription->billing_type === 'yearly') 
            ? $activeSubscription->plan->yearly_products_limit 
            : $activeSubscription->plan->products_limit;

            
        $startDate = $activeSubscription['start_date'];
        $endDate   = $activeSubscription['end_date'];

     
        $currentProductCount = MerchantProductCount::where('user_id', $userId)
            ->where('plan_id', $planId)
            ->where('plan_start_date', $startDate)
            ->where('plan_end_date', $endDate)            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('product_insert_count');

             $remainingLimit = $productLimit - $currentProductCount;
           

        
        if ($remainingLimit <= 0) {  // Changed from == 0 to <= 0
            return [
                'success' => false,
                'can_add' => false,  // Add this for consistency
                'message' => "Product limit exceeded. You can only add {$productLimit} product(s) in this plan. You have already added {$currentProductCount} product(s).",
                'remaining' => $remainingLimit
            ];
        }
        



        return [
            'can_add' => true, 
            'remaining' => $productLimit - $currentProductCount,
            'limit' => $productLimit,
            'total_added' => $currentProductCount
        ];
    }
    catch(\Exception $e)
    {
    	return [
    		'success' => false,
    		'message' => 'Something went wrong' .$e->getMessage()
    	];
    }
    }

    public function getTotalProductsForPlan($userId, $planId, $startDate, $endDate)
    {
        // Use MerchantProductCount model instead of self
        return MerchantProductCount::where('user_id', $userId)
            ->where('plan_id', $planId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('product_insert_count');
    }


    // ======================================================

   public function canAddCustomer($userId)
{
    try {
        $activeSubscription = UserSubscription::with('plan')->where('user_id', $userId)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->latest()
            ->first();

        if (!$activeSubscription) {
            return [
                'success' => false,
                'can_add' => false,  // Add this for consistency
                'message' => 'No active subscription found'
            ];
        }

        $customerLimit = ($activeSubscription->billing_type === 'yearly') 
            ? $activeSubscription->plan->yearly_customer_limit 
            : $activeSubscription->plan->customer_limit;

        $startDate = $activeSubscription['start_date'];
        $endDate   = $activeSubscription['end_date'];

        $totalUser = User::where('merchant_id', $userId)
            ->where('role_id', 11)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        $remainingLimit = $customerLimit - $totalUser;
        
        if ($remainingLimit <= 0) {  // Changed from == 0 to <= 0
            return [
                'success' => false,
                'can_add' => false,  // Add this for consistency
                'message' => "Customer limit exceeded. You can only add {$customerLimit} customer(s) in this plan. You have already added {$totalUser} customer(s).",
                'remaining' => $remainingLimit
            ];
        }
        
        return [
            'success' => true,
            'can_add' => true, 
            'remaining' => $remainingLimit,
            'limit' => $customerLimit,
            'total_added' => $totalUser
        ];
    } catch(\Exception $e) {
        return [
            'success' => false,
            'can_add' => false,  // Add this for consistency
            'message' => 'Something went wrong: ' . $e->getMessage()
        ];
    }
}

    
}