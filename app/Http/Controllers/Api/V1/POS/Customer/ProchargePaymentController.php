<?php

namespace App\Http\Controllers\Api\V1\POS\Customer;

use App\Http\Controllers\Controller;
use App\Services\Api\POS\Customer\ProchargePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Api\POS\Customer\ChargeCardTokenRequest;

class ProchargePaymentController extends Controller
{
    protected $prochargePaymentService;

    public function __construct(ProchargePaymentService $prochargePaymentService)
    {
        $this->prochargePaymentService = $prochargePaymentService;
    }

    public function chargeCardToken(ChargeCardTokenRequest $request)
    {
        $userId = Auth::id();

        try {
            $orderId = $request->order_id ?? null;
            $cardToken = $request->card_token;

            $amount = $request->amount;
            $cardSurchargeAmount = $request->card_surcharge_amount ?? 0;

            $result = $this->prochargePaymentService->chargeCardToken($orderId, $cardToken, $amount, $cardSurchargeAmount, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully.',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Procharge Payment Error', [
                'order_id' => $request->order_id ?? null,
                'user_id' => $userId ?? null,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment.',
            ], 402);
        }
    }
}
