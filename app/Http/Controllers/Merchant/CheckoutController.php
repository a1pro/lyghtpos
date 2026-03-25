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
            $user = Auth::user();
            $cyclosUserId = $user->cyclos_user_id;

            if (!$cyclosUserId) {
                throw new \Exception('User does not have a Cyclos ID');
            }

            $buyerCyclosUserId = $request->customer_id;

            if (!$buyerCyclosUserId) {
                throw new \Exception('Buyer User does not have a Cyclos ID');
            }

            $cart = $this->cartService->getCart();

            if (empty($cart)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }

            $originalTotal = $this->calculateTotal($cart);
            $total = $originalTotal;

            if ($request->payment_method === 'card') {
                $total = $total * 1.035; // 3.5% fee
                $total = round($total, 2);
            }

            // Prepare order items for Cyclos API
            $orderItems = [];
            foreach ($cart as $item) {
                $orderItems[] = [
                    'quantity' => $item['pivot']['quantity'] ?? 1,
                    'price' => floatval($item['price'] ?? 0),
                    'product' => $item['cyclos_product_id'] ?? $item['id'], // Use Cyclos product ID
                ];
            }

            // Prepare delivery address from request or use default
            $deliveryAddress = [
                'addressLine1' => $request->address_line1 ?? '',
                'street' => $request->street ?? '',
                'buildingNumber' => $request->building_number ?? '',
                'zip' => $request->zip ?? '',
                'city' => $request->city ?? '',
                'region' => $request->region ?? '',
                'country' => $request->country ?? 'US'
            ];

            // Prepare order data for createOrder method
            $orderData = [
                'delivery_method' => $request->delivery_method ?? 'pickup',
                'delivery_price' => $request->delivery_price ?? 0,
                'delivery_address' => $deliveryAddress,
                'items' => $orderItems,
                'currency' => 'unit',
                'buyer_id' => $buyerCyclosUserId,
            ];

            // Process payment based on method
            if ($request->payment_method === 'cash') {
                $orderResult = $this->orderService->createOrder($cyclosUserId, $orderData);

                if (!$orderResult['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create order: ' . ($orderResult['message'] ?? 'Unknown error')
                    ], 400);
                }

                // Clear cart
                $this->cartService->emptyCart();

                return response()->json([
                    'success' => true,
                    'message' => 'Order completed successfully',
                    'order_id' => $orderResult['order_id'],
                    'order_data' => $orderResult['data'],
                    'payment_method' => 'cash',
                    'received_amount' => $request->received_amount ?? $total
                ]);
            } elseif ($request->payment_method === 'card') {
                $paymentResult = $this->paymentService->processCardPayment(
                    $request->card_data,
                    $total
                );

                if ($paymentResult['success']) {
                    $orderData['notes'] = "Payment ID: {$paymentResult['payment']->id}\nTransaction ID: {$paymentResult['transaction_id']}\nCard Fee: " . round($total - $originalTotal, 2);

                    $orderResult = $this->orderService->createOrder($cyclosUserId, $orderData);

                    if (!$orderResult['success']) {
                        Log::critical('Payment processed but order creation failed', [
                            'user_id' => $user->id,
                            'payment_id' => $paymentResult['payment']->id,
                            'transaction_id' => $paymentResult['transaction_id'],
                            'error' => $orderResult['message']
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'Payment was processed but order creation failed. Please contact support.',
                            'payment_id' => $paymentResult['payment']->id,
                            'transaction_id' => $paymentResult['transaction_id']
                        ], 500);
                    }

                    $this->cartService->emptyCart();

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment processed successfully',
                        'order_id' => $orderResult['order_id'],
                        'order_data' => $orderResult['data'],
                        'payment_id' => $paymentResult['payment']->id,
                        'transaction_id' => $paymentResult['transaction_id']
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $paymentResult['message']
                    ], 400);
                }
            } elseif ($request->payment_method === 'unyti') {
                $orderData['notes'] = "Unyti User ID: {$request->unyti_user_id}\nStatus: Pending Payment";
                $orderResult = $this->orderService->createOrder($cyclosUserId, $orderData);

                if (!$orderResult['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create order: ' . ($orderResult['message'] ?? 'Unknown error')
                    ], 400);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Unyti payment initiated',
                    'order_id' => $orderResult['order_id'],
                    'order_data' => $orderResult['data'],
                    'payment_method' => 'unyti'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid payment method'
            ], 400);
        } catch (\Exception $e) {
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
