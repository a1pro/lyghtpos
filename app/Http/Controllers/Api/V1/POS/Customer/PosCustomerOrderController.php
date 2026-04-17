<?php

namespace App\Http\Controllers\Api\V1\POS\Customer;

use App\Http\Controllers\Controller;
use App\Services\Api\POS\Auth\PosAuthService;
use App\Services\Api\POS\Customer\PosCustomerOrderService;
use App\Http\Requests\Api\POS\Customer\PosPurchaseOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosCustomerOrderController extends Controller
{
    protected $customerOrderService;

    /**
     * Inject customer order service dependency
     */
    public function __construct(PosCustomerOrderService $customerOrderService)
    {
        $this->customerOrderService = $customerOrderService;
    }

    /**
     * Get all pending orders for authenticated customer
     */
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
                'message' => 'Pending orders retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get complete order history for authenticated customer
     */
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
                'message' => 'Order history retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process order purchase with wallet payment
     */
    public function purchaseOrder(PosPurchaseOrderRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->customerOrderService->purchaseOrder($request->validated());

            DB::commit();
            return response()->json([
                'success' => true,
                'status_code'  => 200,
                'message' => 'Order Purchase Successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            // Check if it's the insufficient balance error
            if ($e->getMessage() == "You don't have sufficient wallet balance to complete the payment") {
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => $e->getMessage()
                ], 400);
            }

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
}
