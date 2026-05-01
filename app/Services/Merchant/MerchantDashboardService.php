<?php
namespace App\Services\Merchant;
use App\Models\JoiningFees;
use App\Models\UserJoiningFees;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use App\Models\MerchantProductCount;
use App\Models\User;
use Auth;
use DB;
use Carbon\Carbon;

class MerchantDashboardService
{
	public function getJoiningFees()
	{
		try{
			return $joiningFees = JoiningFees::select('id','amount')->where('role_id', 2)
    ->where('status', 'active')->get()->toArray();
 
		}
		catch(\Exception $e){
			return [
				'success' => false,
				'message' => 'Something went wrong' .$e->getMessage()
			];
		}
	}

 /**  Check Joining Fees  */
	public function checkJoiningFees()
    {
        try{
			return $join = UserJoiningFees::where('user_id',auth()->id())->count();
        }
        catch(\Exception $e){
            return [
            	'status' => false,
            	'message' => 'Something went wrong' .$e->getMessage()
            ];
    }
}


/*Get product Limit   */
public function getProductLimit($userId)
{
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

		return [
			'success' => true,
			'data'  => ['productLimit' => $productLimit,'currentproductcount'=>$currentProductCount,'remaininglimit' => $remainingLimit]
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



/*   Customer Subscription Detail   */

public function customerSubscriptionDetail($userId)
{
	try{
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
        
		$customerCount = User::where('merchant_id', $userId)
            ->where('role_id', 11)
            ->count();	
        $remainingLimit = $customerLimit - $totalUser;

		return [
			'success' => true,
			'message' => 'Customer subscription data retrieve successfully',
			'data' => [
				'customerLimit' => $customerLimit,
				'totalUser'   => $totalUser,
				'remainingLimit' => $remainingLimit,
				'customerCount'  => $customerCount		
			]

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
}
?>