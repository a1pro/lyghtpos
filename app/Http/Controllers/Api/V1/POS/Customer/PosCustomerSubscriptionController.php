<?php

namespace App\Http\Controllers\Api\V1\POS\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Api\POS\Customer\PosCustomerPlanService;
use App\Http\Requests\Api\POS\Customer\PosCustomPlanPurchase;
use DB;


class PosCustomerSubscriptionController extends Controller
{
    protected $customerPlans;

    public function __construct(PosCustomerPlanService $customerPlans)
    {
    	$this->customerPlans = $customerPlans;
    }

    public function plans()
    {
    	try{
    		$plans = $this->customerPlans->getPlans();

    		return response()->json([
    			'success' => true,
    			'status_code' => 200,
    			'message' => 'Customer Plans retreived successfully',
    			'plans' => $plans
    		],200);
    	}
    	catch(\Exception $e){
    		return response()->json([
    			'success' => false,
    			'status_code' => 500,
    			'message' => 'Something went wrong' .$e->getMessage()
    		],500);
    	}
    }



    /** Customer Purchase Plan */

    public function planPurchase(PosCustomPlanPurchase $request)
    {
    	try{
    		DB::beginTransaction();
    		$subscribe = $this->customerPlans->planPurchase($request->validated());


    		DB::commit();
    		return response()->json(['success'=> true,'status_code'=> 200,'message' => 'Plan Purchase Successfully','data' => $subscribe],200);
    	}
    	catch(\Exception $e){
    		DB::rollback();
    		return response()->json(['success'=>false,'status_code'=>500,'message'=>'Something went wrong'.$e->getMessage],500);
    	}
    }
}
