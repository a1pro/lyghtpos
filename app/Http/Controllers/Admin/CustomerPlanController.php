<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\CustomerPlanService;
use App\Http\Requests\Admin\CustomerPlanRequest;
use DB;


class CustomerPlanController extends Controller
{
    protected $customerPlans;

    public function __construct(CustomerPlanService $customerPlans)
    {
    	$this->customerPlans = $customerPlans;
    }

    public function index()
    {
    	try{
    		$plans = $this->customerPlans->index();
    		if (is_array($plans) && isset($plans['success']) && $plans['success'] === false) {
                throw new \Exception($plans['message']);
            }

    		return view('admin.pages.customer-plan.index',compact('plans'));
    	}
    	catch(\Exception $e){
    		return redirect()->back()->with('error','Something went wrong' .$e->getMessage());
    	}
    }	


    // ============Create Page View=================

    public function create()
    {
    	return view('admin.pages.customer-plan.create');
    }

   /**
	*Store customer Plans Function
	
   */

    public function store(CustomerPlanRequest $request)
    {
    	try{
    		DB::beginTransaction();

    		$result = $this->customerPlans->storePlan($request->validated());


    		if(!$result['success']){
    			throw new \Exception($result['message']);
    		}
    		DB::commit();
    		return redirect()->route('admin.customer-plans.index')->with('success',$result['message']);
    	}
    	catch(\Exception $e){
    			DB::rollback();
    		return redirect()->back()->with('error','Something went wrong: ' .$e->getMessage())->withInput();
    	}
    }


/**

	* Customer Plan Edit Function

*/

	public function edit($id){
		try{
			$result = $this->customerPlans->getPlanById($id);
			 
			if(!$result['success']){
				throw new \Exception($result['message']);
			}
			$plan = $result['plan'];
			return view('admin.pages.customer-plan.edit', compact('plan'));

		}
		catch(\Exception $e)
		{
			return redirect()->route('admin.customer-plans.index')
                ->with('error', 'Plan not found or error: ' . $e->getMessage());
		}
	}

	/**  Customer Update Function */


	public function update(CustomerPlanRequest $request, $id)
	{
		try{
			DB::beginTransaction();
			$result = $this->customerPlans->updatePlan($id,$request->validated());
 			if (!$result['success']) {
                throw new \Exception($result['message']);
            }

			DB::commit();
			return redirect()->route('admin.customer-plans.index')->with('success',$result['message']);
			}
		catch(\Exception $e)
			{
			DB::rollback();
			return redirect()->back()->with('error','Something went wrong' .$e->getMessage())->withInput();
			}
	}


	/** Customer Plan Deleted */
	public function destroy($id)
	{
		try{
			$result = $this->customerPlans->deletePlan($id);
			if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);
				
		   }

		catch(\Exception $e)
		{	
			return respose()->json([
				'success' => false,
				'message' => 'Something went wrong' .$e->getMessage()
			],500);
		}
	}

}
