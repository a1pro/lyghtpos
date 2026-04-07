<?php

namespace App\Services\Api\POS\Customer;
use App\Models\JoiningFees;
use Auth;

class PosJoiningFeesService {

public function getPlansDetail()
{
    try{
        $plans = JoiningFees::where('role_id',11)->get();
        return [
            "plans" => $plans,
            "success" => true,
            "message" => "Plans Fetch Successfully"
        ];
    }
    catch(\Exception $e)
    {
        return[
            'success' => false,
            'message' => "Plans didn't get fetched" .$e->getMessage()
        ];
    }
}



}