<?php

namespace App\Services\Api\POS\Customer;
use App\Models\JoiningFees;
use App\Models\UserJoiningFees;
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

public function getJoiningStatus()
{
    try {
        $userId = Auth::id();
        
        // Check if any record exists for this user
        $hasPaid = UserJoiningFees::where('user_id', $userId)->exists();
        
        return [
            'success' => true,
            'has_paid' => $hasPaid  // true if data exists, false if not
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'has_paid' => false,
            'message' => 'Got issue while fetching status'
        ];
    }
}

}