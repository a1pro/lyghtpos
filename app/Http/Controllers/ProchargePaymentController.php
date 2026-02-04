<?php

namespace App\Http\Controllers;

use App\Services\Procharge\ProchargeClient;

class ProchargePaymentController extends Controller
{
    /**
     * Development-only endpoint
     * Calls Procharge and returns REAL API response
     */
    public function createIntent(ProchargeClient $procharge)
    {
        return response()->json(
            $procharge->createInvoiceIntent()
        );
    }
}
