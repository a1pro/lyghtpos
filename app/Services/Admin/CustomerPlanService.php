<?php
namespace App\Services\Admin;

use App\Models\CustomerPlans;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Str;

class CustomerPlanService
{
   public function index()
   {
   	try{
   		return CustomerPlans::orderBy('sort_order','desc')->orderBy('id','asc')->get();
   	}
   	catch(\Exception $e){
   		return [
   			'success'=>false,
   			'status_code' => 500,
   			'message' => 'Failed to Fetch subscription plans' .$e->getMessage()
   		];
   	}
   }
 	
 	/** 
	* Customer Store Plan Function
	*/

   public function storePlan(array $data)
   {
   	try{
   		$data['slug'] = Str::slug($data['name']);

   		if(isset($data['features']) && is_array($data['features']))
   		{
   			$data['features']  == array_filter($data['features']);
   		}
     
   		$plan = CustomerPlans::create($data);
   		return [
   			 'success' => true,
   			 'plan' => $plan,
   			 'message' => 'Subscription Plan Create Successfully'
   		];
   	}
   	catch(\Exception $e){
   		return[
   			'success' => 'false',
   			'status_code'=> 500,
   			'message' => 'Something went wrong' .$e->getMessage(),
   		];
   	}
   }


/**
 * Customer Plan Edit Function

*/

   public function getPlanById($id)
   {
   	try{
   		$plan = CustomerPlans::findOrFail($id);
   		return [
   			'success' => true,
   			'plan' => $plan
    		];
   	}
   catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Subscription plan not found'
            ];
        } 
   	catch(\Exception $e){
   		return [
   			'success' => false,
   			'status_code' => 500,
   			'message' => 'Something went wrong' .$e->getMessage()
   		];
   	}
   }

   /** Customer Update Plan Section */
   public function updatePlan($id, array $data): array
   {
   	try{
   		$plan = CustomerPlans::findOrFail($id);

   		if (isset($data['name']) && $data['name'] !== $plan->name) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Handle features as JSON
            if (isset($data['features']) && is_array($data['features'])) {
                $data['features'] = array_filter($data['features']);
            }

            $plan->update($data);

            return [
                'success' => true,
                'plan' => $plan,
                'message' => 'Subscription Plan Updated Successfully'
            ];
   	}
   	catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Subscription plan not found'
            ];
        } 

   	catch(\Exception $e){
   		return [
   			'success' => false,
   			'status_code' => 500,
   			'message' => 'Something went wrong' .$e->getMessage()
   		];
   	}
   }


   /** Customer Plan Delete Function */

   public function deletePlan($id)
   {
   	try{
   		$plan = CustomerPlans::findOrFail($id);
   		$plan->delete();

   		return [
   			'success' => true,
   			'message' => 'Subscrption Plan Deleted Successfully'
   		];
   	}
   	catch(ModelNotFoundException $e)
   	{
   		return [
   			'success' => false,
   			'message' => 'Subscription Plan Not Found'
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