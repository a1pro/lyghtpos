<?php

namespace App\Services\FavorMember\CyclosPayment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CyclosPaymentService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $channel;
    protected string $currencyCode;
    protected string $currencySymbol;
    protected string $currencyName;
    protected string $paymentTypeId;
    protected string $adminUserId;
    protected ?string $currentUserId = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.cyclos.base_url'), '/');
        $this->username = (string) config('services.cyclos.username');
        $this->password = (string) config('services.cyclos.password');
        $this->channel = (string) config('services.cyclos.channel', 'webServices');

        $this->currencyCode = (string) config('services.cyclos.currency_code', 'TT');
        $this->currencySymbol = (string) config('services.cyclos.currency_symbol', 'TT');
        $this->currencyName = (string) config('services.cyclos.currency_name', 'TradeTokens');

        // Read only from .env via config/services.php
        $this->paymentTypeId = (string) config('services.cyclos.payment_type_id');
        $this->adminUserId = (string) config('services.cyclos.admin_user_id');

        $this->validateRequiredConfig();

        Log::info('CyclosPaymentService initialized', [
            'currency_code' => $this->currencyCode,
            'currency_symbol' => $this->currencySymbol,
            'admin_user_id' => $this->adminUserId,
            'payment_type_id' => $this->paymentTypeId,
            'environment' => app()->environment(),
        ]);
    }

    protected function validateRequiredConfig(): void
    {
        $missing = [];

        if (empty($this->baseUrl)) {
            $missing[] = 'services.cyclos.base_url';
        }

        if (empty($this->username)) {
            $missing[] = 'services.cyclos.username';
        }

        if (empty($this->password)) {
            $missing[] = 'services.cyclos.password';
        }

        if (empty($this->paymentTypeId)) {
            $missing[] = 'services.cyclos.payment_type_id';
        }

        if (empty($this->adminUserId)) {
            $missing[] = 'services.cyclos.admin_user_id';
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Missing required Cyclos configuration: ' . implode(', ', $missing)
            );
        }
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getAdminUserId(): string
    {
        return $this->adminUserId;
    }

    public function getPaymentTypeId(): string
    {
        return $this->paymentTypeId;
    }

    public function setCurrentUserId(string $userId): void
    {
        $this->currentUserId = $userId;
    }

    public function getUserBalance(string $cyclosUserId): float
    {
        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/accounts";

            Log::info('Fetching user balance from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'url' => $url,
                'currency' => $this->currencyCode,
            ]);

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->get($url);

            if ($response->failed()) {
                Log::error('Failed to fetch Cyclos balance', [
                    'cyclos_user_id' => $cyclosUserId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return 0.00;
            }

            $accounts = $response->json();

            if (!is_array($accounts) || empty($accounts)) {
                return 0.00;
            }

            if (isset($accounts[0]['status']['balance'])) {
                return (float) $accounts[0]['status']['balance'];
            }

            return 0.00;
        } catch (\Exception $e) {
            Log::error('Exception in getUserBalance', [
                'error' => $e->getMessage(),
            ]);
            return 0.00;
        }
    }

    public function getUserTransactions(string $cyclosUserId, int $pageSize = 20, int $pageNumber = 0): array
    {
        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/transactions";

            Log::info('Fetching user transactions from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'url' => $url,
                'pageSize' => $pageSize,
                'currency' => $this->currencyCode,
            ]);

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->get($url, [
                    'pageSize' => $pageSize,
                    'pageNumber' => $pageNumber,
                    'direction' => 'DESC',
                ]);

            Log::info('Transactions API Response', [
                'status' => $response->status(),
                'headers' => [
                    'X-Total-Count' => $response->header('X-Total-Count'),
                ],
            ]);

            if ($response->failed()) {
                Log::error('Failed to fetch transactions', [
                    'cyclos_user_id' => $cyclosUserId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $transactions = $response->json();
            $totalCount = (int) $response->header('X-Total-Count', 0);

            Log::info('Transactions fetched', [
                'total_count' => $totalCount,
                'items_count' => is_array($transactions) ? count($transactions) : 0,
            ]);

            return is_array($transactions) ? $transactions : [];
        } catch (\Exception $e) {
            Log::error('Exception in getUserTransactions', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function formatTransactions(array $rawTransactions): array
    {
        $formatted = [];

        foreach ($rawTransactions as $transaction) {
            $amount = (float) ($transaction['amount'] ?? 0);

            $type = 'credit';
            if ($this->currentUserId) {
                $toUser = $transaction['to']['user']['id'] ?? null;
                $fromUser = $transaction['from']['user']['id'] ?? null;

                if ($toUser && $toUser === $this->currentUserId) {
                    $type = 'credit';
                } elseif ($fromUser && $fromUser === $this->currentUserId) {
                    $type = 'debit';
                }
            }

            $date = isset($transaction['date'])
                ? date('M d, Y H:i', strtotime($transaction['date']))
                : 'N/A';

            $description = $transaction['description'] ?? $transaction['type']['name'] ?? 'Transaction';
            $reference = $transaction['transactionNumber'] ?? $transaction['id'] ?? 'N/A';

            $formatted[] = [
                'id' => $transaction['id'] ?? null,
                'date' => $date,
                'description' => $description,
                'amount' => abs($amount),
                'type' => $type,
                'reference' => $reference,
                'status' => $transaction['status'] ?? 'completed',
                'currency' => $this->currencySymbol,
            ];
        }

        usort($formatted, function ($a, $b) {
            if ($a['date'] === 'N/A' || $b['date'] === 'N/A') {
                return 0;
            }

            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $formatted;
    }

    public function addFunds(string $userCyclosId, float $amount, string $description = ''): array
    {
        try {
            $paymentData = [
                'subject' => $userCyclosId,
                'amount' => $amount,
                'type' => $this->paymentTypeId,
                'description' => $description,
                'currency' => $this->currencyCode,
            ];

            Log::info('Adding funds to Cyclos wallet', [
                'to_user' => $userCyclosId,
                'amount' => $amount,
                'payment_type' => $this->paymentTypeId,
                'from_admin' => $this->adminUserId,
                'currency' => $this->currencyCode,
            ]);

            $paymentUrl = $this->baseUrl . "/api/{$this->adminUserId}/payments";

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->post($paymentUrl, $paymentData);

            if ($response->failed()) {
                $errorData = $response->json();

                Log::error('Cyclos payment failed', [
                    'status' => $response->status(),
                    'error' => $errorData,
                    'payment_type_used' => $this->paymentTypeId,
                    'admin_user_id_used' => $this->adminUserId,
                ]);

                return [
                    'success' => false,
                    'message' => 'Payment failed: ' . json_encode($errorData),
                ];
            }

            $data = $response->json();
            Cache::forget("cyclos_balance_{$userCyclosId}");

            Log::info('Cyclos payment successful', [
                'transaction_id' => $data['id'] ?? null,
                'currency' => $this->currencyCode,
            ]);

            return [
                'success' => true,
                'transaction_id' => $data['id'] ?? null,
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Exception in addFunds', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    public function getUserAccounts(string $cyclosUserId): array
    {
        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/accounts";

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->get($url);

            if ($response->failed()) {
                Log::error('Failed to fetch user accounts', [
                    'cyclos_user_id' => $cyclosUserId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $accounts = $response->json();

            return is_array($accounts) ? $accounts : [];
        } catch (\Exception $e) {
            Log::error('Exception in getUserAccounts', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function debugConfig(): array
    {
        return [
            'admin_user_id' => $this->adminUserId,
            'payment_type_id' => $this->paymentTypeId,
            'currency_code' => $this->currencyCode,
            'currency_symbol' => $this->currencySymbol,
            'currency_name' => $this->currencyName,
            'base_url' => $this->baseUrl,
            'username' => $this->username,
            'channel' => $this->channel,
        ];
    }
}