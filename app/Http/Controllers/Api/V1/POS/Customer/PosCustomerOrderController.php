<?php

namespace App\Http\Controllers\Api\V1\POS\Customer;

use App\Http\Controllers\Controller;
use App\Services\Api\POS\Auth\PosAuthService;
use App\Services\Api\POS\Customer\PosCustomerOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosCustomerOrderController extends Controller
{
    protected $customerOrderService;

    public function __construct(PosCustomerOrderService $customerOrderService)
    {
        $this->customerOrderService = $customerOrderService;
    }

    public function getPendingOrders()
    {
        try {
            $cyclosUserId = Auth::user()->cyclos_user_id;

            if (!$cyclosUserId) {
                throw new \Exception("Cyclos User ID not found for this user");
            }

            $data = $this->customerOrderService->fetchPendingOrders($cyclosUserId);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Peding orders retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request' . $e->getMessage()
            ]);
        }
    }

    public function getOrdersHistory()
    {
        try {
            $cyclosUserId = Auth::user()->cyclos_user_id;

            if (!$cyclosUserId) {
                throw new \Exception("Cyclos User ID not found for this user");
            }

            $data = $this->customerOrderService->fetchOrdersHistory($cyclosUserId);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Peding orders retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request' . $e->getMessage()
            ]);
        }
    }
}
