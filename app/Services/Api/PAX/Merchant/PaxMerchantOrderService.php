<?php

namespace App\Services\Api\PAX\Merchant;

use App\Mail\Pax\Customer\OrderCompleted;
use App\Models\HandpointTransactionLog;
use App\Models\MerchantOrder;
use App\Models\MerchantOrderItem;
use App\Models\User;
use App\Services\Api\POS\Customer\PosCustomerWalletService;
use App\Services\Cyclos\MerchantCyclosOrderService;
use App\Services\FavorMember\CyclosPayment\CyclosPaymentService;
use App\Services\Merchant\MerchantOrderService;
use App\Services\Merchant\Order\MerchantOrderReceiptService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaxMerchantOrderService
{
    protected MerchantCyclosOrderService $merchantCyclosOrderService;
    protected PosCustomerWalletService $customerWallet;
    protected CyclosPaymentService $cyclosPaymentService;
    protected MerchantOrderService $merchantOrderService;

    public function __construct(
        MerchantCyclosOrderService $merchantCyclosOrderService,
        PosCustomerWalletService $customerWallet,
        CyclosPaymentService $cyclosPaymentService,
        MerchantOrderService $merchantOrderService
    ) {
        $this->merchantCyclosOrderService =  $merchantCyclosOrderService;
        $this->customerWallet =  $customerWallet;
        $this->cyclosPaymentService =  $cyclosPaymentService;
        $this->merchantOrderService = $merchantOrderService;
    }

    /**
     * Create a local order and its items from the cart.
     */
    public function createOrder(
        User $customer,
        array $cartItems,
        string $paymentMethod,
        float $productsTotal, // excluding tax amount
        float $taxAmount,
        ?float $cardAmount = null, // excluding tax amount
        ?float $tokenAmount = null, // excluding tax amount
        ?string $notes = null
    ) {
        return DB::transaction(function () use ($customer, $cartItems, $paymentMethod, $productsTotal, $taxAmount, $cardAmount, $tokenAmount, $notes) {
            $merchant = Auth::user();
            $merchantId = $merchant->id;
            $customerCyclosId = $customer->cyclos_user_id;

            // Calculate grand total (products + tax)
            $grandTotal = $productsTotal + $taxAmount;

            // Calculate portions based on payment method
            $tokenPortion = null;
            $cardPortion = null;

            if ($paymentMethod === 'card') {
                /** For card-only, the entire grand total is paid with card (including tax)
                 * We will credit tokens for the total and cyclos will settle order amount (excluding tax)
                 * we will not crediting the tax amount as we will not be deducting tax in cyclos (tax is paid externally with card and not handled in Cyclos)
                 * Only we will make credit with token which required for settling the cyclos order
                 */
                $cardPortion = $grandTotal;
                $tokenPortion = 0;
            } elseif ($paymentMethod === 'cyclos_tokens') {
                /** For token-only, the entire grand total is paid with tokens (including tax)
                 * Cyclos will settle the order amount (excluding tax) and we will handle tax as part of separate token deduction
                 */
                $tokenPortion = $grandTotal;
                $cardPortion = 0;

                $balance = $this->customerWallet->getCustomerWalletBalance($customerCyclosId);
                if ($balance < (float) $tokenPortion) {
                    throw new \Exception("Customer does not have enough tokens! Available: {$balance}, Required: {$tokenPortion}");
                }
            } elseif ($paymentMethod === 'hybrid') {
                /** For hybrid, we expect both cardAmount and tokenAmount to be provided (excluding tax)
                 * The grand total is split between card and token portions, but tax is applied to card only and will be handled separately
                 */
                $tokenPortion = $tokenAmount;
                $cardPortion = $cardAmount;

                if ($tokenPortion > $grandTotal) {
                    throw new \Exception("Token portion ({$tokenPortion}) cannot exceed grand total ({$grandTotal})");
                }

                if ($tokenPortion > 0) {
                    $balance = $this->customerWallet->getCustomerWalletBalance($customerCyclosId);
                    if ($balance < (float) $tokenPortion) {
                        throw new \Exception("Customer does not have enough tokens! Available: {$balance}, Required: {$tokenPortion}");
                    }
                }
            } elseif ($paymentMethod === 'cash') {
                /** For Cash, we will create order with pending status and complete it immediately
                 * with cash payment method, we won't have card or token portions
                 * We assume that this method is called after successful cash payment is confirmed on the client side
                 * We will credit the total amount to the customer's Cyclos account (exluding tax) 
                 * as tax credit is not required as we will not be deducting tax (tax is paid in cash and not handled in Cyclos)
                 * By cyclos setteling the amount (excluding tax)
                 */
                $cardPortion = null;
                $tokenPortion = null;
            } elseif ($paymentMethod === 'pos_mobile') {
                /** For POS Mobile, we will create order with pending status and complete it after successful payment confirmation from POS Lyght Mobile App
                 * We won't have card or token portions as the payment is handled externally via POS Lyght Mobile App
                 * We will handle this with another already built service method and return from here
                 */
                $cardPortion = null;
                $tokenPortion = null;
                try {
                    $order = $this->merchantOrderService->createOrder(
                        $customerCyclosId,
                        $cartItems,
                        $paymentMethod,
                        $productsTotal,
                        $notes,
                        $taxAmount
                    );

                    return $order;
                } catch (\Exception $e) {
                    Log::error('Error processing POS Mobile order', [
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception("Error processing POS Mobile order: " . $e->getMessage());
                }
            }

            // Create the order
            $order = MerchantOrder::create([
                'merchant_id' => $merchantId,
                'customer_id' => $customer->id,
                'customer_cyclos_id' => $customerCyclosId,
                'total_amount' => $productsTotal, // Products total (excl. tax)
                'tax_amount' => $taxAmount,
                'card_amount' => $cardPortion,
                'token_amount' => $tokenPortion,
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'payment_status' => 'pending',
                'notes' => $notes,
                'payment_metadata' => [
                    'created_from_pax' => true,
                    'payment_initiated' => now()->toIso8601String(),
                    'grand_total' => $grandTotal,
                ]
            ]);

            // Create order items
            foreach ($cartItems as $item) {
                MerchantOrderItem::create([
                    'merchant_order_id' => $order->id,
                    'product_cyclos_id' => $item['id'] ?? null,
                    'product_name' => $item['name'] ?? null,
                    'price' => $item['price'] ?? 0,
                    'quantity' => $item['quantity'] ?? 1,
                ]);
            }

            // Create Cyclos order ONLY for products (tax will be handled separately)
            $cyclosItems = array_map(function ($item) {
                return [
                    'product' => $item['id'] ?? null,
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'price' => (float) ($item['price'] ?? 0),
                ];
            }, $cartItems);

            $orderData = [
                'items' => $cyclosItems,
                'notes' => $notes ?? "Order #{$order->order_number} - Products total: {$productsTotal} (Tax of {$taxAmount} will be settled separately)",
            ];

            $cyclosOrder = $this->merchantCyclosOrderService->createOrder($merchant, $customerCyclosId, $orderData);

            if (!$cyclosOrder['success']) {
                throw new \Exception("Cyclos order creation failed: " . ($cyclosOrder['message'] ?? 'Unknown error'));
            }

            if (!$cyclosOrder['order_id']) {
                throw new \Exception("Cyclos order ID not found in response: " . ($cyclosOrder['message'] ?? 'Unknown error'));
            }

            $order->update([
                'cyclos_order_id' => (string) $cyclosOrder['order_id'],
                'payment_metadata' => array_merge($order->payment_metadata ?? [], [
                    'cyclos_order_created_at' => now()->toIso8601String(),
                    'cyclos_order_data' => $cyclosOrder['data'] ?? null,
                ])
            ]);

            // For token-only & cash payments - Auto-complete immediately (no handpoint needed) (no completeOrder call needed)
            if ($paymentMethod == 'cyclos_tokens' || $paymentMethod == 'cash') {
                Log::info('Auto-completing token-only or cash order', [
                    'order_id' => $order->id,
                    'products_amount' => $productsTotal,
                    'tax_amount' => $taxAmount,
                    'grand_total' => $grandTotal,
                    'token_amount' => $tokenPortion
                ]);

                // Process token payment immediately
                $this->completeOrder($order, []);
            }

            Log::info('Pax Merchant order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_method' => $paymentMethod,
                'products_amount' => $productsTotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'card_amount' => $cardPortion,
                'token_amount' => $tokenPortion,
            ]);

            return $order;
        });
    }

    /**
     * Record Handpoint transaction data to the logs table
     */
    public function recordHandpointTransaction(MerchantOrder $order, array $handpointResponse, array $paymentData): ?HandpointTransactionLog
    {
        try {
            $rawResponse = $handpointResponse['raw_response'] ?? [];
            $cardDetails = $handpointResponse['card_details'] ?? [];
            $deviceStatus = $rawResponse['deviceStatus'] ?? [];

            // Prepare log data
            $logData = [
                'merchant_order_id' => $order->id,
                'merchant_id' => $order->merchant_id,
                'customer_id' => $order->customer_id,

                // Transaction Identifiers
                'transaction_id' => $paymentData['transaction_id'] ?? $handpointResponse['transaction_id'] ?? null,
                'transaction_reference' => $paymentData['handpoint_transaction_reference'] ?? $rawResponse['transactionReference'] ?? null,

                // Status
                'status' => $handpointResponse['status'] ?? 'unknown',
                'fin_status' => $paymentData['handpoint_fin_status'] ?? $rawResponse['finStatus'] ?? null,
                'status_message' => $rawResponse['statusMessage'] ?? null,

                // Amounts (convert from cents if needed)
                'amount' => isset($handpointResponse['amount']) ? $handpointResponse['amount'] : (isset($rawResponse['totalAmount']) ? $rawResponse['totalAmount'] / 100 : 0),
                'currency' => $rawResponse['currency'] ?? 'USD',
                'tip_amount' => isset($rawResponse['tipAmount']) ? $rawResponse['tipAmount'] / 100 : null,
                'approved_amount' => isset($rawResponse['approvedAmount']) ? $rawResponse['approvedAmount'] / 100 : null,
                'requested_amount' => isset($rawResponse['requestedAmount']) ? $rawResponse['requestedAmount'] / 100 : null,

                // EMV & Card Details
                'aid' => $paymentData['handpoint_aid'] ?? $rawResponse['aid'] ?? null,
                'arc' => $paymentData['handpoint_arc'] ?? $rawResponse['arc'] ?? null,
                'issuer_response_code' => $paymentData['handpoint_issuer_response_code'] ?? $rawResponse['issuerResponseCode'] ?? null,
                'rrn' => $paymentData['handpoint_rrn'] ?? $rawResponse['rrn'] ?? null,
                'authorization_code' => $paymentData['handpoint_authorization_code'] ?? $rawResponse['authorisationCode'] ?? null,
                'card_last4' => $paymentData['handpoint_card_last4'] ?? $cardDetails['last4'] ?? null,
                'card_brand' => $paymentData['handpoint_card_brand'] ?? $cardDetails['brand'] ?? $rawResponse['cardSchemeName'] ?? null,
                'masked_card_number' => $rawResponse['maskedCardNumber'] ?? null,
                'card_expiry' => $rawResponse['expiryDateMMYY'] ?? null,
                'tender_type' => $rawResponse['tenderType'] ?? null,

                // Terminal Details
                'device_serial' => $handpointResponse['device_serial'] ?? $rawResponse['device_serial'] ?? $deviceStatus['serialNumber'] ?? null,
                'device_status_message' => $deviceStatus['statusMessage'] ?? null,
                'device_battery_status' => isset($deviceStatus['batteryStatus']) ? (string) $deviceStatus['batteryStatus'] : null,

                // Receipt URLs
                'merchant_receipt_url' => $rawResponse['merchantReceipt'] ?? null,
                'customer_receipt_url' => $rawResponse['customerReceipt'] ?? null,

                // Flags
                'is_partial_approval' => $rawResponse['isPartialApproval'] ?? false,
                'is_recovered' => $rawResponse['recoveredTransaction'] ?? false,
                'is_reversal_attempted' => $rawResponse['reversalAttempted'] ?? false,

                // Raw Response and Additional Data
                'raw_response' => $handpointResponse,
                'additional_data' => [
                    'payment_data' => $paymentData,
                    'order_total' => $order->total_amount + $order->tax_amount,
                    'order_payment_method' => $order->payment_method,
                ],
                'transaction_timestamp' => isset($handpointResponse['timestamp']) ? date('Y-m-d H:i:s', strtotime($handpointResponse['timestamp'])) : now(),
            ];

            // Create or update the log
            $log = HandpointTransactionLog::updateOrCreate(
                ['transaction_id' => $logData['transaction_id']],
                $logData
            );

            Log::info('Handpoint transaction recorded successfully', [
                'log_id' => $log->id,
                'transaction_id' => $log->transaction_id,
                'order_id' => $order->id,
            ]);

            return $log;
        } catch (\Exception $e) {
            Log::error('Failed to record Handpoint transaction', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Complete the order after successful payment
     */
    public function completeOrder(MerchantOrder $order, array $paymentData): MerchantOrder
    {
        return DB::transaction(function () use ($order, $paymentData) {
            $paymentResult = $this->processPayment($order, $paymentData);

            if (!$paymentResult['success']) {
                throw new \Exception($paymentResult['message']);
            }

            // Prepare update data
            $updateData = [
                'status' => 'completed',
                'payment_status' => 'paid',
                'paid_at' => now(),
                'payment_metadata' => array_merge($order->payment_metadata ?? [], [
                    'completed_at' => now()->toIso8601String(),
                ])
            ];

            // Add transaction IDs if they exist
            if (isset($paymentData['transaction_id'])) {
                $updateData['card_transaction_id'] = $paymentData['transaction_id'];
                $updateData['payment_metadata']['handpoint_response'] = $paymentData['handpoint_response'] ?? null;
            }

            if (isset($paymentData['handpoint_fin_status'])) {
                $updateData['handpoint_fin_status'] = $paymentData['handpoint_fin_status'];
            }

            if (isset($paymentData['handpoint_aid'])) {
                $updateData['handpoint_aid'] = $paymentData['handpoint_aid'];
            }

            if (isset($paymentData['handpoint_arc'])) {
                $updateData['handpoint_arc'] = $paymentData['handpoint_arc'];
            }

            if (isset($paymentData['handpoint_issuer_response_code'])) {
                $updateData['handpoint_issuer_response_code'] = $paymentData['handpoint_issuer_response_code'];
            }

            if (isset($paymentData['handpoint_rrn'])) {
                $updateData['handpoint_rrn'] = $paymentData['handpoint_rrn'];
            }

            if (isset($paymentData['handpoint_authorization_code'])) {
                $updateData['handpoint_authorization_code'] = $paymentData['handpoint_authorization_code'];
            }

            if (isset($paymentData['handpoint_card_last4'])) {
                $updateData['handpoint_card_last4'] = $paymentData['handpoint_card_last4'];
            }

            if (isset($paymentData['handpoint_card_brand'])) {
                $updateData['handpoint_card_brand'] = $paymentData['handpoint_card_brand'];
            }

            if (isset($paymentData['handpoint_transaction_reference'])) {
                $updateData['handpoint_transaction_reference'] = $paymentData['handpoint_transaction_reference'];
            }

            // Store handpoint log ID if present
            if (isset($paymentData['handpoint_log_id'])) {
                $updateData['payment_metadata']['handpoint_log_id'] = $paymentData['handpoint_log_id'];
            }

            if (isset($paymentResult['cyclos_transaction_id'])) {
                $updateData['cyclos_transaction_id'] = $paymentResult['cyclos_transaction_id'];
            }

            if (isset($paymentResult['tax_transaction_id'])) {
                $updateData['payment_metadata']['tax_transaction_id'] = $paymentResult['tax_transaction_id'];
            }

            if (!empty($paymentData)) {
                $updateData['payment_metadata']['payment_data'] = $paymentData;
            }

            $order->update($updateData);

            Log::info('Order completed successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_method' => $order->payment_method,
                'products_amount' => $order->total_amount,
                'tax_amount' => $order->tax_amount,
                'grand_total' => $order->total_amount + $order->tax_amount,
                'card_transaction_id' => $paymentData['transaction_id'] ?? null,
                'cyclos_transaction_id' => $paymentResult['cyclos_transaction_id'] ?? null,
                'tax_transaction_id' => $paymentResult['tax_transaction_id'] ?? null,
            ]);

            $this->sendOrderReceiptEmail($order);

            return $order;
        });
    }

    /**
     * Process tax as a separate direct token transaction
     */
    public function processTaxPayment(MerchantOrder $order, float $taxAmount, string $description): array
    {
        if ($taxAmount <= 0) {
            return ['success' => true, 'message' => 'No tax to process'];
        }

        try {
            $customer = $order->customer();

            // Create a tax deduction of token from customer
            $taxResult = $this->cyclosPaymentService->deductTokensToSystem(
                $customer->cyclos_user_id,
                $taxAmount,
                $description
            );

            if (!$taxResult['success']) {
                throw new \Exception('Tax payment failed: ' . ($taxResult['message'] ?? 'Unknown error'));
            }

            Log::info('Tax payment processed successfully', [
                'order_id' => $order->id,
                'tax_amount' => $taxAmount,
                'transaction_id' => $taxResult['transaction_id'] ?? null
            ]);

            return [
                'success' => true,
                'transaction_id' => $taxResult['transaction_id'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('Tax payment failed', [
                'order_id' => $order->id,
                'tax_amount' => $taxAmount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process payment based on payment method and amounts
     */
    protected function processPayment(MerchantOrder $order, array $paymentData): array
    {
        try {
            $customer = $order->customer();
            $paymentMethod = $order->payment_method;
            $productsAmount = $order->total_amount; // Excluding tax
            $taxAmount = $order->tax_amount;
            $grandTotal = $productsAmount + $taxAmount;

            $result = [
                'success' => true,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'handpoint_response' => $paymentData,
            ];

            $data = [
                'remarks' => "Order #{$order->order_number} - Products: {$productsAmount}, Tax: {$taxAmount}"
            ];

            // Process based on payment method
            switch ($paymentMethod) {
                case 'card':
                    Log::info('Processing card payment', [
                        'order_id' => $order->id,
                        'products_amount' => $productsAmount,
                        'tax_amount' => $taxAmount,
                        'grand_total' => $grandTotal,
                        'total_to_credit' => $productsAmount
                    ]);

                    // Credit tokens for FULL grand total (products + tax)
                    $creditResult = $this->cyclosPaymentService->addFunds(
                        $order->customer_cyclos_id,
                        $productsAmount,
                        "Token credit for card payment - Order #{$order->order_number} (Total: {$productsAmount})"
                    );

                    if (!$creditResult['success']) {
                        throw new \Exception('Failed to credit tokens: ' . ($creditResult['message'] ?? 'Unknown error'));
                    }

                    $result['credit_transaction_id'] = $creditResult['transaction_id'] ?? null;

                    // Accept order as buyer for products only
                    $cyclosPayment = $this->merchantCyclosOrderService->acceptOrderAsBuyer(
                        $customer,
                        $order->cyclos_order_id,
                        $data
                    );

                    if (!$cyclosPayment['success']) {
                        throw new \Exception('Order acceptance failed: ' . ($cyclosPayment['message'] ?? 'Unknown error'));
                    }

                    $result['cyclos_transaction_id'] = $cyclosPayment['transaction_id'] ?? null;

                    Log::info('Card payment completed successfully', [
                        'order_id' => $order->id,
                        'products_deducted' => $productsAmount,
                        'net_effect' => 'Customer paid with card, net token change: 0'
                    ]);
                    break;

                case 'cyclos_tokens':
                    Log::info('Processing token payment', [
                        'order_id' => $order->id,
                        'products_amount' => $productsAmount,
                        'tax_amount' => $taxAmount,
                        'grand_total' => $grandTotal
                    ]);

                    // Accept order as buyer for products only
                    $cyclosPayment = $this->merchantCyclosOrderService->acceptOrderAsBuyer(
                        $customer,
                        $order->cyclos_order_id,
                        $data
                    );

                    if (!$cyclosPayment['success']) {
                        throw new \Exception('Order acceptance failed: ' . ($cyclosPayment['message'] ?? 'Unknown error'));
                    }

                    $result['cyclos_transaction_id'] = $cyclosPayment['transaction_id'] ?? null;

                    // Process tax as separate transfer
                    $taxResult = $this->processTaxPayment(
                        $order,
                        $taxAmount,
                        "Tax payment for token order #{$order->order_number} - Amount: {$taxAmount}"
                    );

                    if (!$taxResult['success']) {
                        throw new \Exception('Tax payment failed: ' . ($taxResult['message'] ?? 'Unknown error'));
                    }

                    $result['tax_transaction_id'] = $taxResult['transaction_id'] ?? null;

                    Log::info('Token payment completed successfully', [
                        'order_id' => $order->id,
                        'products_deducted' => $productsAmount,
                        'tax_deducted' => $taxAmount,
                        'total_deducted' => $grandTotal,
                        'net_effect' => "Customer lost {$grandTotal} tokens"
                    ]);
                    break;

                case 'hybrid':
                    Log::info('Processing hybrid payment', [
                        'order_id' => $order->id,
                        'products_amount' => $productsAmount,
                        'tax_amount' => $taxAmount,
                        'grand_total' => $grandTotal,
                        'card_amount' => $order->card_amount,
                        'token_amount' => $order->token_amount
                    ]);

                    $cardwithTaxAmount = $order->card_amount + $taxAmount; // Tax on card only

                    // Credit tokens for CARD portion
                    if ($order->card_amount > 0) {
                        $creditResult = $this->cyclosPaymentService->addFunds(
                            $order->customer_cyclos_id,
                            $cardwithTaxAmount,
                            "Token credit for card portion of hybrid payment - Order #{$order->order_number} (Card amount: {$order->card_amount})"
                        );

                        if (!$creditResult['success']) {
                            throw new \Exception('Failed to credit tokens for card portion: ' . ($creditResult['message'] ?? 'Unknown error'));
                        }

                        $result['credit_transaction_id'] = $creditResult['transaction_id'] ?? null;
                    }

                    // Accept order as buyer for products (deducts products amount)
                    $cyclosPayment = $this->merchantCyclosOrderService->acceptOrderAsBuyer(
                        $customer,
                        $order->cyclos_order_id,
                        $data
                    );

                    if (!$cyclosPayment['success']) {
                        throw new \Exception('Order acceptance failed: ' . ($cyclosPayment['message'] ?? 'Unknown error'));
                    }

                    $result['cyclos_transaction_id'] = $cyclosPayment['transaction_id'] ?? null;

                    // Process FULL tax as separate transfer
                    $taxResult = $this->processTaxPayment(
                        $order,
                        $taxAmount,
                        "Tax payment for hybrid order #{$order->order_number} - Amount: {$taxAmount}"
                    );

                    if (!$taxResult['success']) {
                        throw new \Exception('Tax payment failed: ' . ($taxResult['message'] ?? 'Unknown error'));
                    }

                    $result['tax_transaction_id'] = $taxResult['transaction_id'] ?? null;

                    // Calculate net token change
                    $netTokenChange = -$order->token_amount; // Negative means customer lost tokens

                    Log::info('Hybrid payment completed successfully', [
                        'order_id' => $order->id,
                        'card_amount_credited' => $order->card_amount,
                        'products_deducted' => $productsAmount,
                        'tax_deducted' => $taxAmount,
                        'total_deducted' => $grandTotal,
                        'net_effect' => "Customer lost {$order->token_amount} tokens (net change: {$netTokenChange}) and paid {$order->card_amount} via card"
                    ]);
                    break;
                case 'cash':
                    Log::info('Processing cash payment', [
                        'order_id' => $order->id,
                        'products_amount' => $productsAmount,
                        'tax_amount' => $taxAmount,
                        'grand_total' => $grandTotal,
                        'total_to_credit' => $productsAmount
                    ]);

                    // Credit tokens for FULL grand total (products + tax)
                    $creditResult = $this->cyclosPaymentService->addFunds(
                        $order->customer_cyclos_id,
                        $productsAmount,
                        "Token credit for cash payment - Order #{$order->order_number} (Total: {$productsAmount})"
                    );

                    if (!$creditResult['success']) {
                        throw new \Exception('Failed to credit tokens: ' . ($creditResult['message'] ?? 'Unknown error'));
                    }

                    $result['credit_transaction_id'] = $creditResult['transaction_id'] ?? null;

                    // Accept order as buyer for products only
                    $cyclosPayment = $this->merchantCyclosOrderService->acceptOrderAsBuyer(
                        $customer,
                        $order->cyclos_order_id,
                        $data
                    );

                    if (!$cyclosPayment['success']) {
                        throw new \Exception('Order acceptance failed: ' . ($cyclosPayment['message'] ?? 'Unknown error'));
                    }

                    $result['cyclos_transaction_id'] = $cyclosPayment['transaction_id'] ?? null;

                    Log::info('Cash payment completed successfully', [
                        'order_id' => $order->id,
                        'total_credited' => $productsAmount,
                        'products_deducted' => $productsAmount,
                        'net_effect' => 'Customer paid with cash, net token change: 0'
                    ]);
                    break;
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'order_id' => $order->id,
                'payment_method' => $order->payment_method,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send order receipt email to customer (runs in background, doesn't block main flow)
     */
    protected function sendOrderReceiptEmail(MerchantOrder $order): void
    {
        try {
            dispatch(function () use ($order) {
                try {
                    $customer = $order->customer();

                    if (!$customer || !$customer->email) {
                        Log::warning('Cannot send order receipt email - customer has no email', [
                            'order_id' => $order->id,
                            'customer_id' => $customer->id ?? null
                        ]);
                        return;
                    }

                    $receiptService = app(MerchantOrderReceiptService::class);
                    $receiptHtml = $receiptService->generateReceiptHtml($order);

                    Mail::to($customer->email)->send(new OrderCompleted($order, $receiptHtml));

                    Log::info('Order receipt email sent successfully', [
                        'order_id' => $order->id,
                        'customer_email' => $customer->email
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send order receipt email in background', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            })->onQueue('emails');
        } catch (\Exception $e) {
            Log::error('Failed to dispatch order receipt email job', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
