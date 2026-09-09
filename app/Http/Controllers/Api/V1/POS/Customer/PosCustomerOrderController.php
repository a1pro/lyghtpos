<?php

namespace App\Http\Controllers\Api\V1\POS\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\POS\Customer\PosGetReceiptRequest;
use App\Services\Api\POS\Auth\PosAuthService;
use App\Services\Api\POS\Customer\PosCustomerOrderService;
use App\Http\Requests\Api\POS\Customer\PosPurchaseOrderRequest;
use App\Models\MerchantOrder;
use App\Services\Merchant\Order\MerchantOrderReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosCustomerOrderController extends Controller
{
    protected $customerOrderService;
    protected MerchantOrderReceiptService $receiptService;

    /**
     * Inject customer order service dependency
     */
    public function __construct(PosCustomerOrderService $customerOrderService, MerchantOrderReceiptService $receiptService)
    {
        $this->customerOrderService = $customerOrderService;
        $this->receiptService = $receiptService;
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
    public function getOrdersHistory(Request $request)
    {
        try {
            $cyclosUserId = Auth::user()->cyclos_user_id;

            if (!$cyclosUserId) {
                throw new \Exception("Cyclos User ID not found for this user");
            }

            $perPage = 10;

            $data = $this->customerOrderService->fetchOrdersHistory($cyclosUserId, $perPage);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Order history retrieved successfully',
                'data' => $data->items(),
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total()
                ]
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

    /**
     * Get order receipt HTML     
     */
    public function getReceipt(PosGetReceiptRequest $request)
    {
        try {
            $orderId = $request->order_id;

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID is required'
                ], 400);
            }

            $order = MerchantOrder::find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $customerId = Auth::user()->id;
            // Ensure the order belongs to the authenticated customer
            if ($order->customer()->id != $customerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this order receipt'
                ], 403);
            }

            // Only allow completed orders
            if ($order->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Receipt only available for completed orders',
                    'order_status' => $order->status
                ], 400);
            }

            $html = $this->receiptService->generateReceiptHtml($order);

            return response()->json([
                'success' => true,
                'receipt_html' => $html,
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating receipt: ' . $e->getMessage()
            ], 500);
        }
    }
}
