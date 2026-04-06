<?php

namespace App\Services\Api\POS\Customer;

use App\Models\Customer;
use App\Models\MerchantOrder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PosCustomerOrderService
{
    /**
     * Logout POS user
     */
    public function fetchPendingOrders(string $cyclosUserId)
    {
        try {
            $orders = MerchantOrder::where(['customer_cyclos_id' => $cyclosUserId, 'status' => 'pending'])->get()->toArray();
            return $orders ?? [];
        } catch (\Exception $e) {
            Log::error('Error fetching pending orders', [
                'error' => $e->getMessage()
            ]);
        }
    }

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
}
