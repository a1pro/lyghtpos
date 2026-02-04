<?php

namespace App\Services\Procharge;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProchargeClient
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $merchantId;

    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->baseUrl    = config('services.procharge.base_url');
        $this->username   = config('services.procharge.username');
        $this->password   = config('services.procharge.password');
        $this->merchantId = config('services.procharge.merchant_id');
    }

    /**
     * Authenticate with Procharge and return Bearer token
     */
    public function authenticate(): string
    {
        if ($this->accessToken) {
            Log::info('[Procharge] Using cached access token');
            return $this->accessToken;
        }

        Log::info('[Procharge] Authenticating with Procharge', [
            'endpoint' => '/api/authentication/login',
            'base_url' => $this->baseUrl,
            'username' => $this->username,
        ]);

        $response = Http::post(
            $this->baseUrl . '/api/authentication/login',
            [
                'userName' => $this->username,
                'passWord' => $this->password,
                'pin' => "12345678",
                "application" => "procharge"
            ]
        );

        Log::info('[Procharge] Authentication response received', [
            'status_code' => $response->status(),
            'response'    => $response->json(),
        ]);

        if (!$response->successful()) {
            Log::error('[Procharge] Authentication failed', [
                'status_code' => $response->status(),
                'response'    => $response->json(),
            ]);

            throw new \Exception('Procharge authentication failed');
        }

        $this->accessToken = $response->json('access_token');

        Log::info('[Procharge] Authentication successful');

        return $this->accessToken;
    }

    /**
     * CREATE "PAYMENT INTENT" VIA PROCHARGE (INVOICE)
     */
    public function createInvoiceIntent(): array
    {
        Log::info('[Procharge] Creating invoice intent');

        $token = $this->authenticate();

        $payload = [
            'merchant_id'    => $this->merchantId,
            'amount'         => 200,
            'currency'       => 'NGN',
            'description'    => 'Development test payment intent',
            'customer_email' => 'test@gmail.com',
            'reference'      => (string) Str::uuid(),
        ];

        Log::info('[Procharge] Sending invoice creation request', [
            'endpoint' => '/api/gateway/invoice',
            'payload'  => $payload,
        ]);

        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->post(
            $this->baseUrl . '/api/gateway/invoice',
            $payload
        );

        Log::info('[Procharge] Invoice response received', [
            'status_code' => $response->status(),
            'response'    => $response->json(),
        ]);

        if (!$response->successful()) {
            Log::error('[Procharge] Invoice creation failed', [
                'status_code' => $response->status(),
                'response'    => $response->json(),
            ]);
        }

        return [
            'endpoint'    => '/api/gateway/invoice',
            'request'     => $payload,
            'status_code' => $response->status(),
            'response'    => $response->json(),
        ];
    }
}
