<?php

namespace App\Http\Controllers\Api\V1\POS\Customer;

use App\Http\Controllers\Controller;
use App\Services\Api\POS\Customer\PosPayPalPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PosPayPalPaymentController extends Controller
{
    protected $paypalService;

    public function __construct(PosPayPalPaymentService $paypalService)
    {
        $this->paypalService = $paypalService;
    }

    /**
     * Step 1: Create PayPal order and return approval URL
     */
    public function createOrder(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:0.01'
            ]);

            $userId = Auth::id();
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'status_code' => 401,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $result = $this->paypalService->createOrder($request->amount, $userId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'PayPal order created successfully',
                'data' => [
                    'order_id' => $result['order_id'],
                    'approval_url' => $result['approval_url'],  // Send this to React Native
                    'user_joining_fee_id' => $result['user_joining_fee_id']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Step 2: Check if order is approved (call this after user returns from PayPal)
     */
    public function checkOrderStatus(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|string'
            ]);

            $result = $this->paypalService->getPaymentStatus($request->order_id);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Order status retrieved',
                'data' => [
                    'order_status' => $result['status'],
                    'is_approved' => $result['status'] === 'APPROVED',
                    'can_capture' => $result['status'] === 'APPROVED'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Step 3: Capture payment (only after approval)
     */
    public function capturePayment(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|string'
            ]);

            // First check if order is approved
            $statusResult = $this->paypalService->getPaymentStatus($request->order_id);
            
            if (!$statusResult['success']) {
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => 'Failed to check order status'
                ], 400);
            }

            if ($statusResult['status'] !== 'APPROVED') {
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => 'Order not approved yet. Please complete payment in PayPal first. Current status: ' . $statusResult['status'],
                    'data' => [
                        'approval_required' => true,
                        'current_status' => $statusResult['status']
                    ]
                ], 400);
            }

            // Order is approved, now capture
            $result = $this->paypalService->capturePayment($request->order_id);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Payment captured successfully',
                'data' => [
                    'capture_id' => $result['capture_data']['id'] ?? null,
                    'status' => 'COMPLETED'
                ]
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