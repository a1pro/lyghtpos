<?php

namespace App\Services\Api\POS\Customer;

use App\Models\MerchantOrder;
use App\Models\ProchargeTransactionLog;
use App\Models\UserJoiningFees;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProchargePaymentService
{
    protected $apiKey;
    protected $baseUrl;

    protected $applicationName = 'procharge';
    protected $username;
    protected $password;
    protected $pin;

    protected $merchantNumber;
    protected $mode;

    public function __construct()
    {
        $this->mode = config('services.procharge.mode', 'sandbox');

        if ($this->mode === 'live') {
            $this->apiKey = config('services.procharge.live.api_key');
            $this->baseUrl = config('services.procharge.live.api_base_url', 'https://api.procharge.com');

            $this->username = config('services.procharge.live.username');
            $this->password = config('services.procharge.live.password');
            $this->pin = config('services.procharge.live.pin');

            $this->merchantNumber = config('services.procharge.live.merchant_number');
        } else {
            $this->apiKey = config('services.procharge.sandbox.api_key');
            $this->baseUrl = config('services.procharge.sandbox.api_base_url', 'https://dev-api.procharge.com');
            $this->username = config('services.procharge.sandbox.username');
            $this->password = config('services.procharge.sandbox.password');
            $this->pin = config('services.procharge.sandbox.pin');

            $this->merchantNumber = config('services.procharge.sandbox.merchant_number', '889901550594702');
        }

        if (!$this->apiKey) {
            Log::error('Procharge API key not configured properly');
        }
    }

    /**
     * Get Procharge access token
     */
    private function getAccessToken()
    {
        $endpoint = $this->baseUrl . '/api/authentication/login';

        try {
            $payload = [
                'application' => $this->applicationName,
                'userName' => $this->username,
                'passWord' => $this->password,
                'pin' => $this->pin,
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($endpoint, $payload);

            if ($response->successful()) {
                $token = $response->json()['access_token'];
                Log::info('Procharge access token retrieved successfully', ['token' => $token, 'response' => $response->json()]);
                return $token;
            }

            $error = 'Failed to get Procharge access token: ' . $response->body();
            Log::error($error, ['response' => $response->json()]);
            throw new \Exception($error);
        } catch (\Exception $e) {
            Log::error('Procharge token error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function chargeCardToken($orderId, $cardToken, $amount, $customerId = null)
    {
        $endpoint = $this->baseUrl . '/api/transaction';

        try {
            $accessToken = $this->getAccessToken();

            $payload = [
                "merchantNumber" => $this->merchantNumber,
                "transactionCode" => "1",
                "token" => $cardToken,
                "amount" => number_format($amount, 2, '.', ''),
            ];

            Log::info('Charging Procharge card', [
                'order_id' => $orderId ?? null,
                'customer_id' => $customerId ?? null,
                'card_token' => $cardToken,
                'amount' => $amount,
            ]);

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Authorization' => $accessToken,
                'Content-Type' => 'application/json',
            ])->post($endpoint, $payload);

            $responseData = $response->json();

            Log::info('Procharge charge card response', [
                'order_id' => $orderId ?? null,
                'response_code' => $responseData['responseCode'] ?? null,
                'response_text' => $responseData['responseText'] ?? null,
            ]);

            $order = null;
            if ($orderId) {
                $order = MerchantOrder::find($orderId);
                if (!$order) {
                    Log::warning('Order not found', ['order_id' => $orderId]);
                }
            }

            // Check if transaction was APPROVED
            $isApproved = false;
            $responseCode = $responseData['responseCode'] ?? null;
            $authorizationNumber = $responseData['authorizationNumber'] ?? null;
            $responseText = $responseData['responseText'] ?? '';

            // CRITERIA 1: responseCode must be 0
            if ($responseCode === 0) {
                // CRITERIA 2: authorizationNumber must not be empty
                if (!empty($authorizationNumber)) {
                    // CRITERIA 3: responseText should contain "APPROVAL"
                    if (str_contains(strtoupper($responseText), 'APPROVAL')) {
                        $isApproved = true;
                    }
                }
            }

            $log = $this->createTransactionLog(
                $payload,
                $responseData,
                $cardToken,
                $orderId,
                $customerId,
                $isApproved
            );

            if ($response->successful() && !$isApproved) {
                Log::warning('Procharge card charge DECLINED', [
                    'order_id' => $orderId,
                    'response_code' => $responseCode,
                    'response_text' => $responseText,
                    'log_id' => $log->id,
                ]);

                if ($order) {
                    $this->updateOrderWithDeclinedStatus($order, $cardToken, $responseData);
                }

                throw new \Exception($responseText ?: 'Transaction declined');
            }

            if (!$response->successful()) {
                $error = 'Failed to charge Procharge card: ' . $response->body();
                Log::error($error, ['response' => $response->json() ?? null]);

                $log->update(['status' => 'failed']);

                if ($order) {
                    $this->updateOrderWithFailedStatus($order, $cardToken, $error);
                }

                throw new \Exception($error);
            }

            Log::info('Procharge card charged SUCCESSFULLY', [
                'order_id' => $orderId,
                'transaction_id' => $responseData['transactionIdentifier'] ?? null,
                'authorization' => $authorizationNumber,
                'log_id' => $log->id,
            ]);

            if ($order) {
                $this->updateOrderWithSuccessStatus($order, $cardToken, $responseData);
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Procharge charge error: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'amount' => $amount,
            ]);
            throw $e;
        }
    }

    /**
     * Create transaction log entry
     */
    private function createTransactionLog($requestPayload, $responseData, $cardToken, $orderId, $customerId, $isApproved)
    {
        try {
            $amount = $responseData['approvedAmount'] ?? null;
            if (empty($amount) && !is_numeric($amount)) {
                $amount = $requestPayload['amount'] ?? null;
            }
            $amount = is_numeric($amount) ? (float) $amount : null;

            $invoiceId = !empty($responseData['invoiceID']) ? (int) $responseData['invoiceID'] : null;
            $paymentId = !empty($responseData['paymentID']) ? (int) $responseData['paymentID'] : null;

            $log = ProchargeTransactionLog::create([
                // Core transaction fields
                'transaction_identifier' => $responseData['transactionIdentifier'] ?? null,
                'authorization_number' => $responseData['authorizationNumber'] ?? null,
                'invoice_id' => $invoiceId,
                'payment_id' => $paymentId,
                'batch_id' => $responseData['batchID'] ?? null,
                'batch_number' => $responseData['batchNumber'] ?? null,

                // Card details
                'card_type' => $responseData['cardType'] ?? null,
                'card_last_four' => $responseData['cardLastFour'] ?? null,
                'card_scheme' => $responseData['cardScheme'] ?? null,

                // Amount - ensure it's not empty string
                'amount' => $amount,
                'currency' => 'USD',

                // Response status
                'response_code' => isset($responseData['responseCode']) ? (int) $responseData['responseCode'] : null,
                'response_text' => $responseData['responseText'] ?? null,
                'network_response_code' => $responseData['networkResponseCode'] ?? null,
                'host_response' => $responseData['cygmaResponse']['HostResponse'] ?? null,

                // Request data
                'card_token' => $cardToken,
                'order_id' => $orderId,
                'customer_id' => $customerId,

                // Status
                'status' => $isApproved ? 'approved' : 'declined',

                // Payloads
                'request_payload' => $requestPayload,
                'response_payload' => $responseData,

                // Environment
                'environment' => $this->mode,
            ]);

            return $log;
        } catch (\Exception $e) {
            Log::error('Failed to create Procharge transaction log', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
            ]);
            throw $e;
        }
    }

    /**
     * Update order with declined status
     */
    private function updateOrderWithDeclinedStatus($order, $cardToken, $responseData)
    {
        $order->update([
            'procharge_authorization_number' => null,
            'procharge_card_token' => $cardToken,
            'procharge_transaction_id' => $responseData['transactionIdentifier'] ?? null,
            'procharge_invoice_id' => $responseData['invoiceID'] ?? null,
            'procharge_response' => json_encode($responseData),
        ]);
    }

    /**
     * Update order with failed status
     */
    private function updateOrderWithFailedStatus($order, $cardToken, $error)
    {
        $order->update([
            'procharge_card_token' => $cardToken,
            'procharge_response' => json_encode(['error' => $error]),
        ]);
    }

    /**
     * Update order with success status
     */
    private function updateOrderWithSuccessStatus($order, $cardToken, $responseData)
    {
        $order->update([
            'procharge_authorization_number' => $responseData['authorizationNumber'] ?? null,
            'procharge_card_token' => $cardToken,
            'procharge_transaction_id' => $responseData['transactionIdentifier'] ?? null,
            'procharge_invoice_id' => $responseData['invoiceID'] ?? null,
            'procharge_response' => json_encode($responseData),
        ]);
    }
}
