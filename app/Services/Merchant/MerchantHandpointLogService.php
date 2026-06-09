<?php

namespace App\Services\Merchant;

use App\Models\HandpointTransactionLog;
use App\Models\MerchantOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MerchantHandpointLogService
{
    /**
     * Get all handpoint logs for authenticated merchant
     */
    public function getLogs(int $perPage = 15): LengthAwarePaginator
    {
        $merchantId = Auth::id();

        $query = HandpointTransactionLog::where('merchant_id', $merchantId)
            ->with('order');

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get all handpoint logs without pagination
     */
    public function getAllLogs(): Collection
    {
        $merchantId = Auth::id();

        $query = HandpointTransactionLog::where('merchant_id', $merchantId)
            ->with('order');

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get single handpoint log by ID
     */
    public function getLogById(int $id): ?HandpointTransactionLog
    {
        $merchantId = Auth::id();

        return HandpointTransactionLog::where('merchant_id', $merchantId)
            ->with(['order', 'customer'])
            ->find($id);
    }

    /**
     * Get handpoint log by transaction reference
     */
    public function getLogByTransactionReference(string $reference): ?HandpointTransactionLog
    {
        $merchantId = Auth::id();

        return HandpointTransactionLog::where('merchant_id', $merchantId)
            ->where('transaction_reference', $reference)
            ->first();
    }

    /**
     * Get logs by order ID
     */
    public function getLogsByOrder(int $orderId): Collection
    {
        $merchantId = Auth::id();

        return HandpointTransactionLog::where('merchant_id', $merchantId)
            ->where('merchant_order_id', $orderId)
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
