<?php

namespace App\Services\Cyclos;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CyclosVoucherService
{
    protected $baseUrl;
    protected $apiUsername;
    protected $apiPassword;

    public function __construct()
    {
        $this->baseUrl = config('services.cyclos.base_url');
        $this->apiUsername = config('services.cyclos.username');
        $this->apiPassword = config('services.cyclos.password');
    }

    /**
     * Generate vouchers in Cyclos
     */
    public function generateVouchers(array $data): array
    {
        if (!isset($data['count']) || !isset($data['amount']) || !isset($data['user'])) {
            throw new \Exception('Missing required fields: count, amount, and user are required');
        }

        $payload = [
            "count" => (int) $data['count'],
            "amount" => (string) number_format($data['amount'], 2, '.', ''),
            "type" => "user_voucher",
            "inactive" => false,
            "user" => $data['user']
        ];

        Log::info('Calling Cyclos Voucher API', [
            'url' => $this->baseUrl . '/api/vouchers/generate',
            'user' => $data['user'],
            'count' => $data['count']
        ]);

        try {
            $response = Http::withBasicAuth($this->apiUsername, $this->apiPassword)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'confirmationPassword' => $this->apiPassword
                ])
                ->withOptions([
                    'verify' => app()->environment('local') ? false : true,
                    'timeout' => 30
                ])
                ->post($this->baseUrl . '/api/vouchers/generate', $payload);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMessage = 'Cyclos API Error: ';
                
                if (isset($errorBody['propertyErrors'])) {
                    foreach ($errorBody['propertyErrors'] as $field => $errors) {
                        $errorMessage .= "$field: " . implode(', ', $errors) . '; ';
                    }
                } elseif (isset($errorBody['detail'])) {
                    $errorMessage .= $errorBody['detail'];
                } elseif (isset($errorBody['error'])) {
                    $errorMessage .= $errorBody['error'];
                } else {
                    $errorMessage .= $response->body();
                }

                throw new \Exception($errorMessage);
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Cyclos Voucher API call failed', [
                'error' => $e->getMessage(),
                'user' => $data['user']
            ]);
            throw $e;
        }
    }

    /**
     * Change voucher expiration date
     * POST /vouchers/{key}/change-expiration
     */
    public function changeExpiration(string $voucherId, string $expirationDate): array
    {
        try {
            $payload = [
                'expirationDate' => $expirationDate
            ];

            Log::info('Changing voucher expiration', [
                'voucher_id' => $voucherId,
                'expiration_date' => $expirationDate
            ]);

            $response = Http::withBasicAuth($this->apiUsername, $this->apiPassword)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'confirmationPassword' => $this->apiPassword
                ])
                ->post($this->baseUrl . '/api/vouchers/' . $voucherId . '/change-expiration', $payload);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMessage = 'Failed to change expiration: ';
                
                if (isset($errorBody['propertyErrors'])) {
                    foreach ($errorBody['propertyErrors'] as $field => $errors) {
                        $errorMessage .= "$field: " . implode(', ', $errors) . '; ';
                    }
                } elseif (isset($errorBody['detail'])) {
                    $errorMessage .= $errorBody['detail'];
                } else {
                    $errorMessage .= $response->body();
                }

                throw new \Exception($errorMessage);
            }

            return ['success' => true, 'message' => 'Expiration date updated successfully'];

        } catch (\Exception $e) {
            Log::error('Failed to change voucher expiration', [
                'voucher_id' => $voucherId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a voucher
     * POST /vouchers/{key}/cancel
     */
    public function cancelVoucher(string $voucherId): array
    {
        try {
            Log::info('Cancelling voucher', [
                'voucher_id' => $voucherId
            ]);

            $response = Http::withBasicAuth($this->apiUsername, $this->apiPassword)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'confirmationPassword' => $this->apiPassword
                ])
                ->post($this->baseUrl . '/api/vouchers/' . $voucherId . '/cancel');

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMessage = 'Failed to cancel voucher: ';
                
                if (isset($errorBody['propertyErrors'])) {
                    foreach ($errorBody['propertyErrors'] as $field => $errors) {
                        $errorMessage .= "$field: " . implode(', ', $errors) . '; ';
                    }
                } elseif (isset($errorBody['detail'])) {
                    $errorMessage .= $errorBody['detail'];
                } else {
                    $errorMessage .= $response->body();
                }

                throw new \Exception($errorMessage);
            }

            return ['success' => true, 'message' => 'Voucher cancelled successfully'];

        } catch (\Exception $e) {
            Log::error('Failed to cancel voucher', [
                'voucher_id' => $voucherId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Search vouchers in Cyclos
     */
    public function searchVouchers(array $filters = []): array
    {
        try {
            $response = Http::withBasicAuth($this->apiUsername, $this->apiPassword)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'confirmationPassword' => $this->apiPassword
                ])
                ->get($this->baseUrl . '/api/vouchers', $filters);

            if (!$response->successful()) {
                throw new \Exception('Failed to search vouchers: ' . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Failed to search vouchers in Cyclos', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}