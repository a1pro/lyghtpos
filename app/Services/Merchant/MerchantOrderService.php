<?php

namespace App\Services\Merchant;

use App\Models\MerchantOrder;
use App\Models\MerchantOrderItem;
use App\Services\Cyclos\MerchantCyclosOrderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MerchantOrderService
{
    protected $customerWallet;
    protected $cyclosPayment;
    protected $merchantCyclosOrderService;

    public function __construct(
        MerchantCyclosOrderService $merchantCyclosOrderService
    ) {
        $this->merchantCyclosOrderService =  $merchantCyclosOrderService;
    }

    /**
     * Create a local order and its items from the cart.
     */
    public function createOrder(
        string $customerCyclosId,
        array $cartItems,
        string $paymentMethod,
        float $totalAmount,
        ?string $notes = null
    ): MerchantOrder {
        return DB::transaction(function () use ($customerCyclosId, $cartItems, $paymentMethod, $totalAmount, $notes) {
            $merchant = Auth::user();
            $merchantId = $merchant->id;

            // Determine status based on payment method
            $status = 'pending';
            $paymentStatus = 'pending';

            if ($paymentMethod === 'cash') {
                $status = 'completed';
                $paymentStatus = 'paid';
            }

            // Create the order
            $order = MerchantOrder::create([
                'merchant_id' => $merchantId,
                'customer_cyclos_id' => $customerCyclosId,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'notes' => $notes,
            ]);

            // Create order items
            foreach ($cartItems as $item) {
                $quantity = $item['pivot']['quantity'] ?? 1;
                $price = floatval($item['price'] ?? 0);

                MerchantOrderItem::create([
                    'merchant_order_id' => $order->id,
                    'product_cyclos_id' => $item['id'] ?? null,
                    'product_name' => $item['name'] ?? null,
                    'price' => $price,
                    'quantity' => $quantity,
                ]);
            }

            if ($paymentMethod === 'pos_mobile') {
                $items = array_map(function ($item) {
                    return [
                        'product' => $item['product_cyclos_id'] ?? $item['id'] ?? null,
                        'quantity' => (int) ($item['pivot']['quantity'] ?? $item['quantity'] ?? 1),
                        'price' => (float) ($item['price'] ?? 0),
                    ];
                }, $cartItems);

                $orderData = [
                    'items' => $items,
                ];

                $cyclosOrder = $this->merchantCyclosOrderService->createOrder($merchant, $customerCyclosId, $orderData);

                if (!$cyclosOrder['success']) {
                    throw new \Exception("Cyclos order creation failed: " . ($cyclosOrder['message'] ?? 'Unknown error'));
                }

                if (!$cyclosOrder['order_id']) {
                    throw new \Exception("Cyclos order ID not found in response: " . ($cyclosOrder['message'] ?? 'Unknown error'));
                }

                $order->update([
                    'cyclos_order_id' => $cyclosOrder['order_id']
                ]);
            }

            Log::info('Merchant order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_method' => $paymentMethod,
                'status' => $status,
            ]);

            return $order;
        });
    }
}
