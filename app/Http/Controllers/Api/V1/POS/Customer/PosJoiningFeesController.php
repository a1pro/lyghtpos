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
        $this->joiningFees = $joiningFees;
    }

    public function getPlans()
    {
        try {
            $result = $this->joiningFees->getPlansDetail();
            
            if (!$result['success']) {
                throw new \Exception($result['message']);
            }
            
            $plans = $result['plans'];
            
            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Plans Fetch Successfully',
                'plans' => $plans->map(function($plan) {
                    return [
                        'id' => $plan->id,
                        'role' => $plan->role()->first()->name ?? 'N/A',
                        'status' => $plan->status,
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
                'has_paid' => $result['has_paid']
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