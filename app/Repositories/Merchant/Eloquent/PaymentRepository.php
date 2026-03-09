<?php

namespace App\Repositories\Merchant\Eloquent;

use App\Models\MerchantPayment;
use App\Repositories\Merchant\Interfaces\PaymentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function all(): Collection
    {
        return MerchantPayment::latest()->get();
    }

    public function find(int $id): ?MerchantPayment
    {
        return MerchantPayment::find($id);
    }

    public function create(array $data): MerchantPayment
    {
        return MerchantPayment::create($data);
    }

    public function update(MerchantPayment $payment, array $data): MerchantPayment
    {
        $payment->update($data);
        return $payment;
    }

    public function findByTransactionId(string $transactionId): ?MerchantPayment
    {
        return MerchantPayment::where('transaction_id', $transactionId)->first();
    }
}