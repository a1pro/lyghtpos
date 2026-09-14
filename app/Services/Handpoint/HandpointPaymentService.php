<?php

namespace App\Services\Handpoint;

use App\Models\Merchant;
use App\Models\MerchantOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HandpointPaymentService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.handpoint.base_url', 'https://cloud.handpoint.com'), '/');
    }

    /**
     * Process a MOTO (Remote) refund against an original Handpoint sale.
     *
     * Uses the original sale's GUID so no card / terminal presence is required.
     * Handpoint MOTO refund expects the amount in MAJOR decimal units (e.g. "1.00").
     *
     * @return array{success: bool, txn_id: ?string, full_response: ?array, message: string}
     */
    public function refundTransaction(Merchant $merchant, MerchantOrder $order, float $refundAmount): array
    {
        try {
            $handpointApiKey = $merchant->handpoint_api_key ?? null;
            if (!$handpointApiKey) {
                return $this->failure('Merchant Handpoint API key not found.');
            }

            $originalGuid = $order->card_transaction_id ?? null;
            if (!$originalGuid) {
                return $this->failure('Original Handpoint transaction GUID not found.');
            }

            $payload = [
                'originalGuid' => $originalGuid,
                'amount'       => number_format($refundAmount, 2, '.', ''),
                'currency'     => $order->currency ?? 'USD',
            ];

            $response = Http::withHeaders([
                'ApiKeyCLoud' => $handpointApiKey,
                'Content-Type' => 'application/json',
            ])
                ->post($this->baseUrl . '/moto/refund', $payload);

            $body = $response->json() ?? [];

            // Success: HTTP 2xx + body httpStatus 200 + a refund GUID returned
            $isSuccess = $response->successful()
                && (int) ($body['httpStatus'] ?? 0) === 200
                && !empty($body['guid']);

            if ($isSuccess) {
                return [
                    'success'       => true,
                    'txn_id'        => $body['guid'],
                    'full_response' => $body,
                    'message'       => $body['issuerResponseText'] ?? 'Handpoint card refund processed.',
                ];
            }

            $message = $this->extractErrorMessage($body) ?? 'Handpoint refund failed.';

            Log::warning('Handpoint MOTO refund declined', [
                'order_id'    => $order->id,
                'http_status' => $response->status(),
                'body'        => $body,
            ]);

            return [
                'success'       => false,
                'txn_id'        => null,
                'full_response' => $body,
                'message'       => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Handpoint MOTO refund error', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return $this->failure($e->getMessage());
        }
    }

    /**
     * Resolve a human-readable error message from a Handpoint error body.
     */
    protected function extractErrorMessage(array $body): ?string
    {
        return $body['error']['message']
            ?? $body['error']['details']['description']
            ?? $body['issuerResponseText']
            ?? $body['statusMessage']
            ?? $body['errorMessage']
            ?? null;
    }

    /**
     * Standard failure shape.
     */
    protected function failure(string $message): array
    {
        return [
            'success'       => false,
            'txn_id'        => null,
            'full_response' => null,
            'message'       => $message,
        ];
    }
}
