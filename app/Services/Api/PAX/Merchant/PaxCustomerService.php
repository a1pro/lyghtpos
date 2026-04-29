<?php

namespace App\Services\Api\PAX\Merchant;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PaxCustomerService
{
    /**
     * Fetch customers for authenticated merchant
     */
    public function fetchMerchantCustomers(User $merchant, string $search, int $perPage, int $page)
    {
        $query = User::select('id', 'first_name', 'last_name', 'email', 'phone', 'status', 'created_at')
            ->where('merchant_id', $merchant->id)
            ->where('status', 'active');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $search . '%']);
            });
        }

        $customers = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'customers' => $customers->items(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'total_pages' => $customers->lastPage(),
                'has_next_page' => $customers->hasMorePages(),
                'has_prev_page' => $customers->currentPage() > 1,
            ]
        ];
    }
}
