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
     * Create PayPal order for joining fees
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
                    'approval_url' => $result['approval_url'],
                    'user_joining_fee_id' => $result['user_joining_fee_id']
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'status_code' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Capture PayPal payment after approval
     */
    public function capturePayment(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|string'
            ]);

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
                    'status' => $result['capture_data']['status'] ?? 'COMPLETED',
                    'amount' => $result['capture_data']['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? null,
                    'user_joining_fee' => $result['user_joining_fee']
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'status_code' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus(Request $request)
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
                'message' => 'Payment status retrieved successfully',
                'data' => [
                    'paypal_status' => $result['status'],
                    'local_status' => $result['payment_status'],
                    'user_joining_fee' => $result['user_joining_fee']
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'status_code' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's payment history
     */
    public function getPaymentHistory()
    {
        try {
            $userId = Auth::id();
            
            $payments = UserJoiningFees::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Payment history retrieved successfully',
                'data' => [
                    'payments' => $payments->map(function($payment) {
                        return [
                            'id' => $payment->id,
                            'amount' => $payment->amount,
                            'transaction_id' => $payment->transaction_id,
                            'status' => $payment->status,
                            'created_at' => $payment->created_at,
                            'updated_at' => $payment->updated_at
                        ];
                    }),
                    'total_count' => $payments->count(),
                    'total_amount' => $payments->where('status', 'completed')->sum('amount')
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