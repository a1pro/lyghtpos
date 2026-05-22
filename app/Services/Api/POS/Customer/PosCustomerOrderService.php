<?php

namespace App\Services\Api\POS\Customer;

use App\Models\Customer;
use App\Models\MerchantOrder;
use App\Models\User;
use App\Services\Api\PAX\Merchant\PaxMerchantOrderService;
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
    protected PaxMerchantOrderService $paxMerchantOrderService;

    public function __construct(
        PosCustomerWalletService $customerWallet,
        CyclosPaymentService $cyclosPayment,
        MerchantCyclosOrderService $merchantCyclosOrderService,
        PaxMerchantOrderService $paxMerchantOrderService
    ) {
        $this->customerWallet = $customerWallet;
        $this->cyclosPayment =  $cyclosPayment;
        $this->merchantCyclosOrderService =  $merchantCyclosOrderService;
        $this->paxMerchantOrderService = $paxMerchantOrderService;
    }

    /**
     * Fetch pending orders with status pending for a particular customer
     */
    public function fetchPendingOrders(string $cyclosUserId)
    {
        try {
            $orders = MerchantOrder::where(['customer_cyclos_id' => $cyclosUserId, 'payment_status' => 'pending'])
                ->with('items:id,merchant_order_id,product_name,quantity,price')
                ->orderBy('created_at', 'desc')
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
            $orders = MerchantOrder::where('customer_cyclos_id', $cyclosUserId)
                ->where('payment_status', '!=', 'pending')
                ->with('items:id,merchant_order_id,product_name,quantity,price')
                ->orderBy('paid_at', 'desc')
                ->get()
                ->toArray();
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

            if (!$orderDetail['cyclos_order_id']) {
                throw new \Exception("Cyclos Order not found");
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

            $taxAmount = $orderDetail->tax_amount ?? 0;
            $totalAmountWithTax = $orderDetail->total_amount + $taxAmount;

            if($balance < $totalAmountWithTax) {
                throw new \Exception("Insufficient wallet balance. Required: {$totalAmountWithTax}, Available: {$balance}");
            }

            $acceptData = [
                'remarks' => 'Order accepted by buyer with pos id:' . $customer->id
            ];

            $cyclosOrder = $this->merchantCyclosOrderService->acceptOrderAsBuyer($customer, $orderDetail['cyclos_order_id'], $acceptData);

            if (!$cyclosOrder['success']) {
                throw new \Exception("Error accepting cyclos order as buyer: " . ($cyclosOrder['message'] ?? 'Unknown error'));
            }

            // Update order status
            MerchantOrder::where('id', $orderId)->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'paid_at' => now(),
                'notes' => "Order marked as accepted from buyer.",
            ]);

            if ($orderDetail->tax_amount > 0) {
                // Process tax as separate transfer
                $taxResult = $this->paxMerchantOrderService->processTaxPayment(
                    $orderDetail,
                    $orderDetail->tax_amount,
                    "Tax payment for lyght app order completion trigger #{$orderDetail->order_number} - Amount: {$orderDetail->tax_amount}"
                );

                if (!$taxResult['success']) {
                    throw new \Exception('Tax payment failed: ' . ($taxResult['message'] ?? 'Unknown error'));
                }

                $result['tax_transaction_id'] = $taxResult['transaction_id'] ?? null;
                MerchantOrder::where('id', $orderId)->update([
                    'payment_metadata' => array_merge($orderDetail->payment_metadata ?? [], [
                        'tax_transaction_id' => $taxResult['transaction_id'] ?? null,
                    ])
                ]);
            }

            Log::info('Order completed - Buyer accepted order', [
                'order_id' => $orderId,
                'customer_id' => $cyclosUserId,
                'customer_balance' => $balance,
            ]);

            return [
                'success' => true,
                'message' => 'Order processed successfully. Order accepted by buyer.',
                'status' => 'completed',
                'order_id' => $orderId,
            ];
        } catch (\Exception $e) {
            Log::error('Error while purchasing the order', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            if (isset($orderId) && $orderId) {
                MerchantOrder::where('id', $orderId)->update([
                    'status' => 'pending',
                    'payment_status' => 'failed',
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
