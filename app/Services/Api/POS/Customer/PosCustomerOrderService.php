<?php

namespace App\Services\Api\POS\Customer;

use App\Models\Customer;
use App\Models\MerchantOrder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Auth;
use Illuminate\Validation\ValidationException;
use App\Services\Api\POS\Customer\PosCustomerWalletService;
use App\Services\Cyclos\MerchantCyclosOrderService;
use App\Services\FavorMember\CyclosPayment\CyclosPaymentService;

class PosCustomerOrderService
{
    protected $customerWallet;
    protected $cyclosPayment;
    protected $merchantCyclosOrderService;

    public function __construct(
        PosCustomerWalletService $customerWallet,
        CyclosPaymentService $cyclosPayment,
        MerchantCyclosOrderService $merchantCyclosOrderService
    ) {
        $this->customerWallet = $customerWallet;
        $this->cyclosPayment =  $cyclosPayment;
        $this->merchantCyclosOrderService =  $merchantCyclosOrderService;
    }

    /**
     * Fetch pending orders with status pending for a particular customer
     */
    public function fetchPendingOrders(string $cyclosUserId)
    {
        try {
            $orders = MerchantOrder::where(['customer_cyclos_id' => $cyclosUserId, 'status' => 'pending'])
                ->get()->toArray();

            return $orders ?? [];
        } catch (\Exception $e) {
            Log::error('Error fetching pending orders', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Fetch all orders for a particular
     */
    public function fetchOrdersHistory(string $cyclosUserId)
    {
        try {
            $orders = MerchantOrder::where(['customer_cyclos_id' => $cyclosUserId])->get()->toArray();
            return $orders ?? [];
        } catch (\Exception $e) {
            Log::error('Error fetching pending orders', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Purchase order
     */
    public function purchaseOrder(array $data)
    {
        try {
            $cyclosUserId = Auth::user()->cyclos_user_id;
            $balance = $this->customerWallet->getCustomerWalletBalance($cyclosUserId);

            $orderId = $data['order_id'] ?? null;

            if (!$orderId) {
                throw new \Exception("Order ID is required");
            }

            $orderDetail = $this->orderDetail($orderId);

            if (!$orderDetail) {
                throw new \Exception("Order not found");
            }

            if ($orderDetail->status == 'completed') {
                throw new \Exception("Order already completed");
            }

            if ($orderDetail->payment_method != 'pos_mobile') {
                throw new \Exception("Order does not belong to mobile user");
            }

            $customer = $orderDetail->customer();

            if (!$customer || !$customer->cyclos_username || !$customer->cyclos_user_password) {
                throw new \Exception("Customer configuration error");
            }

            $orderAmount = $orderDetail['total_amount'];

            // Check if customer has sufficient balance
            if ($orderAmount > $balance) {
                throw new \Exception("You don't have sufficient wallet balance to complete the payment");
            }

            $merchantId = $orderDetail['merchant_id'];

            $merchant = $orderDetail->merchant;
            $merchantCyclosID = $merchant->cyclos_user_id;

            if (!$merchantCyclosID) {
                throw new \Exception("Merchant configuration error");
            }

            $items = $orderDetail->items->map(function ($item) {
                return [
                    'product' => $item->product_cyclos_id,
                    'quantity' => (int) $item->quantity,
                    'price' => (float) $item->price,
                ];
            })->toArray();

            $orderData = [
                'items' => $items,
            ];

            $cyclosOrder = $this->merchantCyclosOrderService->createOrder($merchant, $customer->cyclos_user_id, $orderData);

            if (!$cyclosOrder['success']) {
                MerchantOrder::where('id', $orderId)->update([
                    'payment_status' => 'failed',
                    'status' => 'pending'
                ]);
                throw new \Exception("Cyclos Order Creation failed: " . ($merchantCredit['message'] ?? 'Unknown error'));
            }

            // Credit merchant from customer
            $merchantCredit = $this->cyclosPayment->addFundsFromCustomer(
                $merchantCyclosID,
                $orderAmount,
                'Order #' . $orderId . ' Cyclos Order ID:' . $cyclosOrder['order_id'] ?? null . ' - Payment to merchant',
                $customer
            );

            if (!$merchantCredit['success']) {
                MerchantOrder::where('id', $orderId)->update([
                    'payment_status' => 'failed',
                    'status' => 'pending'
                ]);
                throw new \Exception("Payment failed: " . ($merchantCredit['message'] ?? 'Unknown error'));
            }

            // Update order status
            MerchantOrder::where('id', $orderId)->update([
                'cyclos_order_id' => $cyclosOrder['order_id'] ?? null,
                'payment_status' => 'paid',
                'status' => 'completed',
                'transaction_id' => $merchantCredit['transaction_id'] ?? null,
                'paid_at' => now(),
                'notes' => "Customer {$cyclosUserId} has balance {$balance} TT. Amount {$orderAmount} TT deducted from admin account. Customer needs to pay admin separately."
            ]);

            Log::info('Order completed - Merchant paid from admin', [
                'order_id' => $orderId,
                'customer_id' => $cyclosUserId,
                'customer_balance' => $balance,
                'merchant_id' => $merchantCyclosID,
                'amount' => $orderAmount,
                'merchant_transaction' => $merchantCredit['transaction_id'] ?? null
            ]);

            return [
                'success' => true,
                'message' => 'Payment processed successfully. Merchant has been credited.',
                'status' => 'paid',
                'order_id' => $orderId,
                'cyclos_order_id' => $cyclosOrder['order_id'] ?? null,
                'amount' => $orderAmount,
                'transaction_id' => $merchantCredit['transaction_id'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('Error while purchasing the order', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            if (isset($orderId) && $orderId) {
                MerchantOrder::where('id', $orderId)->update([
                    'status' => 'pending',
                    'payment_status' => 'failed'
                ]);
            }

            throw $e;
        }
    }

    /**
     * Get order details
     */
    protected function orderDetail($orderId)
    {
        try {
            $order = MerchantOrder::with('items', 'merchant')->find($orderId);

            if (!$order) {
                Log::error('Order not found', ['order_id' => $orderId]);
                return null;
            }

            return $order;
        } catch (\Exception $e) {
            Log::error('Error while fetching order detail', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
            return null;
        }
    }
}
