<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\Merchant\MerchantCartService;
use App\Services\Merchant\PaymentService;
// use App\Services\Merchant\OrderService; // You'll need to create this
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(
        private MerchantCartService $cartService,
        private PaymentService $paymentService,
        // private OrderService $orderService // We'll create this
    ) {}

    /**
     * Process checkout with payment
     */
    public function processCheckout(Request $request)
    {
        try {
            DB::beginTransaction();

            // Get cart items
            $cart = $this->cartService->getCart();

            if (empty($cart)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }

            // Calculate total
            $total = $this->calculateTotal($cart);

            // Apply card fee if applicable
            if ($request->payment_method === 'card') {
                $total = $total * 1.035; // 3.5% fee
                $total = round($total, 2);
            }

            // Process payment based on method
            if ($request->payment_method === 'cash') {
                // For cash, just create order without payment record
                // $order = $this->orderService->createOrder($cart, $total, 'cash', [
                //     'received_amount' => $request->received_amount,
                // ]);

                // Clear cart
                $this->cartService->emptyCart();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Order completed successfully',
                    'order_id' => "777777",
                    'payment_method' => 'cash',
                    'received_amount' => $request->received_amount
                ]);
            } elseif ($request->payment_method === 'card') {
                // Process card payment with Authorize.net
                $paymentResult = $this->paymentService->processCardPayment(
                    $request->card_data,
                    $total
                );

                if ($paymentResult['success']) {
                    // Create order with payment record
                    // $order = $this->orderService->createOrder(
                    //     $cart,
                    //     $total,
                    //     'card',
                    //     [
                    //         'payment_id' => $paymentResult['payment']->id,
                    //         'transaction_id' => $paymentResult['transaction_id'],
                    //         'card_fee' => round($total - $this->calculateTotal($cart), 2)
                    //     ]
                    // );

                    // Clear cart
                    $this->cartService->emptyCart();

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment processed successfully',
                        'order_id' => "777777",
                        'payment_id' => $paymentResult['payment']->id,
                        'transaction_id' => $paymentResult['transaction_id']
                    ]);
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => $paymentResult['message']
                    ], 400);
                }
            } elseif ($request->payment_method === 'unyti') {
                // For Unyti, create pending order (since payment is external)
                // $order = $this->orderService->createOrder($cart, $total, 'unyti', [
                //     'unyti_user_id' => $request->unyti_user_id,
                //     'status' => 'pending_payment'
                // ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Unyti payment initiated',
                    'order_id' => "7777",
                    'payment_method' => 'unyti'
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout processing error', [
                'user_id' => auth()->id(),
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
    public function getCheckoutData()
    {
        try {
            $cart = $this->cartService->getCart();
            $total = $this->calculateTotal($cart);

            return response()->json([
                'success' => true,
                'cart' => $cart,
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
