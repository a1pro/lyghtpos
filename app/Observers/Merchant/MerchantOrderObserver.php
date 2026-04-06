<?php

namespace App\Observers\Merchant;

use App\Models\MerchantOrder;

class MerchantOrderObserver
{
    /**
     * Handle the MerchantOrder "creating" event.
     */
    public function creating(MerchantOrder $order): void
    {
        if (empty($order->order_number)) {
            $order->order_number = $this->generateUniqueOrderNumber();
        }
    }

    /**
     * Generate a unique order number.
     * Format: ORD-YYYYMMDD-XXXX (where XXXX is random).
     */
    protected function generateUniqueOrderNumber(): string
    {
        $prefix = 'ORD-' . date('Ymd') . '-';
        do {
            $number = $prefix . strtoupper(substr(uniqid(), -6));
        } while (MerchantOrder::where('order_number', $number)->exists());

        return $number;
    }
}
