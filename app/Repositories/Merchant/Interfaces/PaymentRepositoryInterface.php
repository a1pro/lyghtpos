<?php

namespace App\Repositories\Merchant\Interfaces;

use App\Models\MerchantPayment;
use Illuminate\Database\Eloquent\Collection;

interface PaymentRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?MerchantPayment;
    public function create(array $data): MerchantPayment;
    public function update(MerchantPayment $payment, array $data): MerchantPayment;
    public function findByTransactionId(string $transactionId): ?MerchantPayment;
}