<?php

namespace App\Http\Controllers\Api\V1\PAX\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PAX\Merchant\PaxCustomerCompleteOrderRequest;
use App\Http\Requests\Api\PAX\Merchant\PaxCustomerCreateOrderRequest;
use App\Http\Requests\Api\PAX\Merchant\PaxGetReceiptRequest;
use App\Services\Merchant\Order\MerchantOrderReceiptService;
use App\Models\MerchantOrder;
use App\Models\User;
use App\Services\Api\PAX\Merchant\PaxMerchantOrderService;
use App\Services\Merchant\MerchantOrderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class PaxOrderController extends Controller
{
    protected PaxMerchantOrderService $paxMerchantOrderService;
    protected MerchantOrderReceiptService $receiptService;

    public function __construct(PaxMerchantOrderService $paxMerchantOrderService, MerchantOrderReceiptService $receiptService)
    {
        $this->paxMerchantOrderService =  $paxMerchantOrderService;
        $this->receiptService = $receiptService;
    }

    /**
     * Process checkout with payment
     */
    public function createCustomerOrder(PaxCustomerCreateOrderRequest $request)
    {
        try {
            Log::info('Creating order for pax merchant', [
                'request_all' => $request->all(),
            ]);

            $merchant = Auth::user();

            $merchantAddress = $merchant->address;
            if (!$merchantAddress) {
                throw new \Exception('Merchant address not configured for order creation!');
            }

            $customerId = $request->customer_id;
            $customer = User::where('id', $customerId)->first();

            if (!$customer) {
                throw new \Exception('Customer not found!');
            }

            $customerCyclosId = $customer->cyclos_user_id;
            if (!$customerCyclosId) {
                throw new \Exception('Customer not configured properly!');
            }

            $paymentMethod = $request->payment_method;
            $totalAmount = $request->total_amount;
            $productsTotal = $request->amount; // excluding tax amount
            $taxAmount = $request->tax_amount;
            $cardAmount = $request->card_amount;
            $tokenAmount = $request->token_amount;
            $products = $request->products;
            $notes = $request->notes ?? null;

            if ($paymentMethod == 'card') {
                $tokenAmount = null;
            } elseif ($paymentMethod == 'cyclos_tokens') {
                $cardAmount = null;
            } elseif ($paymentMethod == 'pos_mobile' && $paymentMethod == 'cash') {
                $cardAmount = null;
                $tokenAmount = null;
            }

            // Create pending order
            $order = $this->paxMerchantOrderService->createOrder(
                $customer,
                $products,
                $paymentMethod,
                $productsTotal,
                $taxAmount,
                $cardAmount,
                $tokenAmount,
                $notes
            );

            $responseData = [
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_method' => $paymentMethod,
                'status' => $order->status,
                'amount' => $productsTotal,
                'total_amount' => $totalAmount,
            ];

            // Add payment-specific information
            if ($paymentMethod === 'card') {
                $responseData['message'] = "Card payment required: {$totalAmount}";
                $responseData['amount_to_charge'] = $totalAmount;
            } elseif ($paymentMethod === 'cyclos_tokens') {
                $responseData['message'] = "Token payment will be processed from Cyclos wallet";
                $responseData['token_amount'] = $tokenAmount;
            } elseif ($paymentMethod === 'hybrid') {
                $responseData['message'] = "Hybrid payment: Card {$cardAmount} + Tokens {$tokenAmount}";
                $responseData['card_amount'] = $cardAmount;
                $responseData['token_amount'] = $tokenAmount;
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Order creation error', [
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
     * Complete order after successful payment
     */
    public function completeCustomerOrder(PaxCustomerCompleteOrderRequest $request)
    {
        try {
            $orderId = $request->order_id;

            $order = MerchantOrder::where('id', $orderId)
                ->where('merchant_id', Auth::id())
                ->first();

            if (!$order) {
                throw new \Exception('Order not found');
            }

            if ($order->status !== 'pending') {
                throw new \Exception('Order is not in pending state');
            }

            Log::info('Completing order for pax merchant', [
                'order_id' => $orderId,
                'request_all' => $request->all(),
            ]);

            // Extract Handpoint EMV details from the response
            $handpointResponse = $request->handpoint_response;
            $rawResponse = $handpointResponse['raw_response'] ?? [];
            $cardDetails = $handpointResponse['card_details'] ?? [];

            // Prepare payment data from Handpoint
            $paymentData = [
                'transaction_id' => $request->transaction_id,
                'handpoint_response' => $request->handpoint_response,
                'payment_timestamp' => now()->toIso8601String(),

                'handpoint_aid' => $rawResponse['aid'] ?? null,
                'handpoint_arc' => $rawResponse['arc'] ?? null,
                'handpoint_issuer_response_code' => $rawResponse['issuerResponseCode'] ?? null,
                'handpoint_rrn' => $rawResponse['rrn'] ?? null,
                'handpoint_authorization_code' => $rawResponse['authorisationCode'] ?? null,
                'handpoint_card_last4' => $cardDetails['last4'] ?? null,
                'handpoint_card_brand' => $cardDetails['brand'] ?? null,
                'handpoint_transaction_reference' => $rawResponse['transactionReference'] ?? $handpointResponse['transaction_reference'] ?? null,
            ];

            // Complete the order
            $completedOrder = $this->paxMerchantOrderService->completeOrder($order, $paymentData);

            return response()->json([
                'success' => true,
                'message' => 'Order completed successfully',
                'order_id' => $completedOrder->id,
                'order_number' => $completedOrder->order_number,
                'status' => $completedOrder->status,
                'payment_status' => $completedOrder->payment_status,
                'transaction_id' => $completedOrder->payment_transaction_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Order completion error', [
                'order_id' => $request->order_id ?? null,
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
     * Get order receipt HTML     
     */
    public function getReceipt(PaxGetReceiptRequest $request)
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

            $merchantId = Auth::id();
            // Ensure the order belongs to the authenticated merchant
            if ($order->merchant_id != $merchantId) {
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

            $html = $this->receiptService->generateReceiptHtml($order, true);

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
