<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\Checkout\MerchantCheckoutRequest;
use App\Services\Merchant\MerchantCartService;
use App\Services\Merchant\MerchantOrderService;
use App\Services\Merchant\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(
        private MerchantCartService $cartService,
        private PaymentService $paymentService,
        private MerchantOrderService $orderService
    ) {}

    /**
     * Process checkout with payment
     */
    public function processCheckout(Request $request)
    {
        try {
            $customerCyclosId = $request->customer_id;

            if (!$customerCyclosId) {
                throw new \Exception('Customer ID is missing');
            }

            $cart = $this->cartService->getCart();
            if (empty($cart)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }

            $total = $this->calculateTotal($cart);
            $paymentMethod = $request->payment_method;

            // Validate allowed payment methods
            $allowedMethods = ['cash', 'pax', 'pos_mobile', 'unyti'];
            if (!in_array($paymentMethod, $allowedMethods)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment method'
                ], 400);
            }

            // Prepare notes based on payment method
            $notes = null;
            if ($paymentMethod === 'unyti') {
                $notes = "Unyti User ID: {$request->unyti_user_id}\nStatus: Pending payment via Unyti credits";
            } elseif ($paymentMethod === 'pax') {
                $notes = "Sent to PAX device – pending approval";
            } elseif ($paymentMethod === 'pos_mobile') {
                $notes = "Sent to POS mobile app – pending approval";
            }

            // Create local order
            $order = $this->orderService->createOrder(
                $customerCyclosId,
                $cart,
                $paymentMethod,
                $total,
                $notes
            );

            // Clear cart after successful order creation
            $this->cartService->emptyCart();

            // Build response
            $responseData = [
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_method' => $paymentMethod,
                'status' => $order->status,
            ];

            if ($paymentMethod === 'cash') {
                $responseData['message'] = 'Order completed with cash payment';
                $responseData['received_amount'] = $request->received_amount ?? $total;
            } elseif ($paymentMethod === 'unyti') {
                $responseData['message'] = 'Unyti payment initiated. Order is pending.';
            } else {
                $responseData['message'] = 'Order created successfully. Waiting for payment/approval.';
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Checkout processing error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get checkout data
     */
    public function getCheckoutData(MerchantCheckoutRequest $request)
    {
        try {
            $cyclosCustomerId = decryptVal($request->cid);

            $cart = $this->cartService->getCart();
            $total = $this->calculateTotal($cart);

            return response()->json([
                'success' => true,
                'cart' => $cart,
                'customerid' => $cyclosCustomerId,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate cart total
     */
    private function calculateTotal($cart)
    {
        if (empty($cart)) return 0;

        return array_reduce($cart, function ($sum, $item) {
            return $sum + (($item['price'] ?? 0) * ($item['pivot']['quantity'] ?? 1));
        }, 0);
    }
}
