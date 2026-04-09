<?php

namespace App\Services\Api\POS\Customer;

use App\Models\TokenBundle;
use Illuminate\Support\Facades\Log;

class PosTokenBundleService
{
    public function fetchActiveTokenBundles()
    {
        try {
            $bundles = TokenBundle::select('id', 'tokens', 'base_amount', 'system_fee', 'discounted')
                ->where('status', 'active')->get()->toArray();

            return $bundles ?? [];
        } catch (\Exception $e) {
            Log::error('Error fetching active bundles', [
                'error' => $e->getMessage()
            ]);
        }
    }
}