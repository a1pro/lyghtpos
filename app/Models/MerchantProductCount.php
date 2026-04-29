<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantProductCount extends Model
{
    protected $fillable = ['user_id', 'plan_id', 'product_insert_count', 'plan_start_date', 'plan_end_date'];
    
    /**
     * Get the total products added for a specific plan period
     */
    public static function getTotalProductsForPlan($userId, $planId, $startDate, $endDate)
    {
        return self::where('user_id', $userId)
            ->where('plan_id', $planId)
            ->where('plan_start_date', $startDate)
            ->where('plan_end_date', $endDate)
            ->sum('product_insert_count');
    }
    
    /**
     * Check if user can add more products
     */
    public static function canAddProducts($userId, $count = 1)
    {
        // Get user's active subscription
        $activeSubscription = UserSubscription::where('user_id', $userId)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->latest()
            ->first();
            
        if (!$activeSubscription) {
            return ['can_add' => false, 'message' => 'No active subscription found'];
        }
        
        // Get the plan details
        $plan = SubscriptionPlan::find($activeSubscription->plan_id);
        if (!$plan) {
            return ['can_add' => false, 'message' => 'Plan not found'];
        }
        
        // Determine the product limit based on billing type
        $productLimit = ($activeSubscription->billing_type === 'yearly') 
            ? $plan->yearly_products_limit 
            : $plan->products_limit;
            
        // If no limit, allow unlimited products
        if ($productLimit === null || $productLimit === 0) {
            return ['can_add' => true, 'remaining' => PHP_INT_MAX];
        }
        
        // Get total products added during this subscription period
        $totalAdded = self::getTotalProductsForPlan(
            $userId, 
            $activeSubscription->plan_id,
            $activeSubscription->start_date,
            $activeSubscription->end_date
        );
        
        $remaining = $productLimit - $totalAdded;
        
        if ($remaining < $count) {
            return [
                'can_add' => false, 
                'message' => "Product limit exceeded. You can only add {$productLimit} product(s) in this plan. You have already added {$totalAdded} product(s).",
                'remaining' => $remaining
            ];
        }
        
        return [
            'can_add' => true, 
            'remaining' => $remaining,
            'limit' => $productLimit,
            'total_added' => $totalAdded
        ];
    }
}