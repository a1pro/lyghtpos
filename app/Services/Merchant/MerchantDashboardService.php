<?php
namespace App\Services\Merchant;
use App\Models\JoiningFees;
use App\Models\UserJoiningFees;
use Auth;
use DB;
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

}
?>