<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\ChangeQuantityRequest;
use App\Http\Requests\Cart\RemoveFromCartRequest;
use App\Models\User;
use App\Services\Merchant\MerchantCartService;
use App\Services\Merchant\PaymentService;
use App\Services\Merchant\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MerchantCartController extends Controller
{
    protected MerchantCartService $cartService;
    protected PaymentService $paymentService;
    protected TransactionService $transactionService;

    public function __construct(
        MerchantCartService $cartService,
        PaymentService $paymentService,
        TransactionService $transactionService
    ) {
        $this->cartService = $cartService;
        $this->paymentService = $paymentService;
        $this->transactionService = $transactionService;
    }

    /**
     * Display the cart.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->wantsJson()) {
            $cart = $this->cartService->getCart();
            return response()->json($cart);
        }

        return view('merchant.pages.cart.index');
    }

    /**
     * Display the checkout page.
     */
    public function checkout(Request $request): View|JsonResponse
    {
        if ($request->wantsJson()) {
            // Return checkout data for AJAX requests
            return $this->getCheckoutData($request);
        }

        return view('merchant.pages.checkout.index');
    }

    /**
     * Get checkout data (cart items, total, customer info)
     */
    public function getCheckoutData(Request $request): JsonResponse
    {
        try {
            $encryptedCustomerId = $request->input('cid');

            if (!$encryptedCustomerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer ID is required'
                ], 400);
            }

            // Decrypt the customer ID
            $customerId = decryptVal($encryptedCustomerId);

            // Get cart items
            $cart = $this->cartService->getCart();
            $cartItems = $cart['items'] ?? [];

            // Calculate total
            $total = 0;
            foreach ($cartItems as $item) {
                $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
            }

            return response()->json([
                'success' => true,
                'cart' => $cartItems,
                'total' => round($total, 2),
                'customerid' => $encryptedCustomerId
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get checkout data', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load checkout data'
            ], 500);
        }
    }

    /**
     * Process checkout payment
     */
    // public function processCheckout(Request $request): JsonResponse
    // {
    //     try {
    //         $validated = $request->validate([
    //             'payment_method' => 'required|in:cash,card,unyti',
    //             'amount' => 'required|numeric|min:0',
    //             'customer_id' => 'required|string',
    //             'received_amount' => 'required_if:payment_method,cash|numeric|min:0',
    //             'card_data' => 'required_if:payment_method,card|array',
    //             'unyti_user_id' => 'required_if:payment_method,unyti|string'
    //         ]);
            
    //         // Decrypt customer ID
    //         $customerId = decryptVal($validated['customer_id']);
    //         $customer = User::where('cyclos_user_id', $customerId)->first();
            
    //         if (!$customer) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Customer not found'
    //             ], 404);
    //         }
            
    //         // Get current cart
    //         $cart = $this->cartService->getCart();
    //         $cartItems = $cart['items'] ?? [];
    //         $totalAmount = $validated['amount'];
            
    //         if (empty($cartItems)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Cart is empty'
    //             ], 400);
    //         }
            
    //         $transaction = null;
            
    //         // Process based on payment method
    //         switch ($validated['payment_method']) {
    //             case 'card':
    //                 // Process card payment
    //                 $paymentResult = $this->paymentService->processCardPayment(
    //                     $validated['card_data'],
    //                     $totalAmount
    //                 );
                    
    //                 if (!$paymentResult['success']) {
    //                     return response()->json([
    //                         'success' => false,
    //                         'message' => $paymentResult['message']
    //                     ], 400);
    //                 }
                    
    //                 // Create transaction with fee calculations
    //                 $transaction = $this->transactionService->createCardPaymentTransaction(
    //                     $paymentResult,
    //                     $totalAmount,
    //                     $customer->id,
    //                     $customer->cyclos_user_id,
    //                     $cartItems
    //                 );
                    
    //                 // Clear the cart after successful payment
    //                 $this->cartService->emptyCart();
                    
    //                 return response()->json([
    //                     'success' => true,
    //                     'message' => 'Payment processed successfully',
    //                     'order_id' => $transaction->id,
    //                     'transaction_id' => $transaction->transaction_id,
    //                     'amount_paid' => $totalAmount,
    //                     'card_fee' => $transaction->platform_fee,
    //                     'amount_after_fee' => $transaction->amount_after_platform_fee
    //                 ]);
                    
    //             case 'cash':
    //                 // Create transaction for cash payment
    //                 $transaction = $this->transactionService->createCashPaymentTransaction(
    //                     $totalAmount,
    //                     $validated['received_amount'],
    //                     $customer->id,
    //                     $customer->cyclos_user_id,
    //                     $cartItems
    //                 );
                    
    //                 // Clear the cart
    //                 $this->cartService->emptyCart();
                    
    //                 return response()->json([
    //                     'success' => true,
    //                     'message' => 'Cash payment recorded successfully',
    //                     'order_id' => $transaction->id,
    //                     'transaction_id' => $transaction->transaction_id,
    //                     'amount_paid' => $validated['received_amount'],
    //                     'change_due' => round($validated['received_amount'] - $totalAmount, 2)
    //                 ]);
                    
    //             case 'unyti':
    //                 // Create transaction for Unyti payment
    //                 $transaction = $this->transactionService->createUnytiPaymentTransaction(
    //                     $totalAmount,
    //                     $validated['unyti_user_id'],
    //                     $customer->id,
    //                     $customer->cyclos_user_id,
    //                     $cartItems
    //                 );
                    
    //                 // Clear the cart
    //                 $this->cartService->emptyCart();
                    
    //                 return response()->json([
    //                     'success' => true,
    //                     'message' => 'Unyti payment initiated successfully',
    //                     'order_id' => $transaction->id,
    //                     'transaction_id' => $transaction->transaction_id
    //                 ]);
                    
    //             default:
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Invalid payment method'
    //                 ], 400);
    //         }
            
    //     } catch (\Exception $e) {
    //         \Log::error('Checkout processing failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
            
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to process checkout: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    /**
     * Add product to cart.
     */
    public function store(AddToCartRequest $request): JsonResponse
    {
        $productId = $request->product_id;

        if (!$productId) {
            return response()->json([
                'message' => 'Product ID is required'
            ], 400);
        }

        $result = $this->cartService->addToCart($productId, 1);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 400);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Change quantity of a cart item.
     */
    public function changeQty(ChangeQuantityRequest $request): JsonResponse
    {
        $result = $this->cartService->updateQuantity(
            $request->product_id,
            $request->quantity
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 400);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Remove product from cart.
     */
    public function delete(RemoveFromCartRequest $request): JsonResponse
    {
        $result = $this->cartService->removeFromCart($request->product_id);

        return response()->json(['success' => $result['success']]);
    }

    /**
     * Empty the entire cart.
     */
    public function empty(Request $request): JsonResponse
    {
        $result = $this->cartService->emptyCart();

        return response()->json(['success' => $result['success']]);
    }

    /**
     * Get Customers / Shoppers for cart
     */
    public function getCustomers(Request $request): JsonResponse
    {
        $merchantId = Auth::id();

        $customers = User::where('merchant_id', $merchantId)
            ->whereNotNull('cyclos_user_id')
            ->whereIn('role_id', [8, 11]) // 8 = Favor member, 11 = Merchant Customer
            ->get(['id', 'first_name', 'last_name', 'cyclos_user_id']);

        // Transform the data to send encrypted cyclos_user_id as id
        $transformedCustomers = $customers->map(function ($customer) {
            return [
                'id' => encryptVal($customer->cyclos_user_id),
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'name' => $customer->first_name . ' ' . $customer->last_name,
            ];
        });

        return response()->json($transformedCustomers);
    }
}
