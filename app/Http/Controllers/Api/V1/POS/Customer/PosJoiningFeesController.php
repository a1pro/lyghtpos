<?php

namespace App\Http\Controllers\Api\V1\POS\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Api\POS\Customer\PosJoiningFeesService;

class PosJoiningFeesController extends Controller
{
    protected $joiningFees;

    public function __construct(PosJoiningFeesService $joiningFees)
    {
        $this->joiningFees = $joiningFees;  // ✅ Fixed: Added assignment
    }

    public function getPlans()
    {
        try {
            $result = $this->joiningFees->getPlansDetail();  // Returns array
            
            // Check if service returned success
            if (!$result['success']) {
                throw new \Exception($result['message']);
            }
            
            $plans = $result['plans'];  // Get the plans collection
            
            return response()->json([
                'success' => true,
                'status_code' => 200,  // ✅ Fixed: Changed from 500 to 200
                'message' => 'Plans Fetch Successfully',
                'plans' => $plans->map(function($plan) {
                    return [
                        'id' => $plan->id,
                        'amount' => $plan->amount
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getPaymentStatus()
    {
        try {
            $result = $this->joiningFees->getJoiningStatus();
            
            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Status fetched successfully',
                'has_paid' => $result['has_paid']  // Returns true if data exists, false if not
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
}