<?php
 namespace App\Services\Api\POS\Customer;
 use App\Models\CustomerPlans;
 use App\Models\CustomerPlanPurchase;
 use App\Http\Requests\Api\POS\Customer\PosCustomPlanPurchase;
 use Auth;
 use Carbon\Carbon;

 class PosCustomerPlanService
 {
 	

 	/** Customer Plans Show Api */

 	public function getPlans()
 	{
 		try{
 			$plans = CustomerPlans::where('is_active',1)->get();

 			return [
 				'success' => true,
 				'plans' => $plans
 			];
 		}
 		catch(\Exception $e){
 			return [
 				'success' => false,
 				'message' => 'Something went wrong' .$e->getMessage()
 			];
 		}
 	}


/** Customer Plan Purchase  */


	public function planPurchase(array $data): array
	{
		try{
			$userId = Auth::id();
			if(!$userId){
				return [
    'success' => false,
    'status_code' => 401,
    'message' => 'Authentication Required'
];
			}

			$billingType = $data['billing_type'];

			$startDate = Carbon::now();

			$endDate = match($billingType){
				'monthly' => Carbon::now()->addMonth()->endOfDay(),
				'yearly'  => Carbon::now()->addYear()->endOfDay(),
				'default' => throw new InvalidArgumentException('Invalid Billing Type')
			};


				$plans = CustomerPlanPurchase::create([
					'user_id' => $userId,
					'plan_id' => $data['plan_id'],
					'start_date' => $startDate,
					'end_date' => $endDate,
					'amount_paid' => $data['amount_paid'],
					'transaction_id' => $data['transaction_id'] ?? NULL,
					'payment_method' => $data['payment_method'] ?? NULL,

				]);

				return [
					'success' => true,
					'plans' => $plans
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