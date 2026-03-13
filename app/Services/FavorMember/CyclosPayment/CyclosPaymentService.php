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
    protected string $currency;
    protected string $paymentTypeId;
    protected string $adminUserId;
    protected ?string $currentUserId = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.cyclos.base_url'), '/');
        $this->username = config('services.cyclos.username');
        $this->password = config('services.cyclos.password');
        $this->channel = config('services.cyclos.channel', 'webServices');
        $this->currency = config('authorize.currency', 'USD');
        $this->paymentTypeId = config('services.cyclos.payment_type_id', '7157739016488738988');
        $this->adminUserId = config('services.cyclos.admin_user_id', '7157739016491622572');
    }

    /**
     * Set the current user ID for transaction type detection
     */
    public function setCurrentUserId(string $userId): void
    {
        $this->currentUserId = $userId;
    }

    /**
     * Get user's wallet balance from Cyclos
     */
    public function getUserBalance(string $cyclosUserId): float
    {
        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/accounts";
            
            Log::info('Fetching user balance from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'url' => $url
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
                    'body' => $response->body()
                ]);
                return 0.00;
            }

            $accounts = $response->json();
            
            if (!is_array($accounts) || empty($accounts)) {
                return 0.00;
            }

            if (isset($accounts[0]['status']['balance'])) {
                return floatval($accounts[0]['status']['balance']);
            }

            return 0.00;

        } catch (\Exception $e) {
            Log::error('Exception in getUserBalance', [
                'error' => $e->getMessage()
            ]);
            return 0.00;
        }
    }

    /**
     * Get user's transaction history from Cyclos
     * FIXED: Properly handles the response format (direct array, not wrapped in pageItems)
     */
    public function getUserTransactions(string $cyclosUserId, int $pageSize = 20, int $pageNumber = 0): array
    {
        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/transactions";
            
            Log::info('🔍 Fetching user transactions from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'url' => $url,
                'pageSize' => $pageSize
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

            Log::info('📦 Transactions API Response', [
                'status' => $response->status(),
                'success' => $response->successful(),
                'headers' => [
                    'X-Total-Count' => $response->header('X-Total-Count'),
                    'X-Page-Size' => $response->header('X-Page-Size'),
                    'X-Current-Page' => $response->header('X-Current-Page'),
                ]
            ]);

            if ($response->failed()) {
                Log::error('❌ Failed to fetch transactions', [
                    'cyclos_user_id' => $cyclosUserId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            // The response is a direct array of transactions (not wrapped in pageItems)
            $transactions = $response->json();
            
            // Get total count from headers
            $totalCount = (int) $response->header('X-Total-Count', 0);
            
            Log::info('✅ Cyclos transactions response', [
                'cyclos_user_id' => $cyclosUserId,
                'total_count' => $totalCount,
                'items_count' => is_array($transactions) ? count($transactions) : 0,
                'is_array' => is_array($transactions),
                'sample' => is_array($transactions) && count($transactions) > 0 ? array_keys($transactions[0]) : null
            ]);

            // Return the direct array of transactions
            return is_array($transactions) ? $transactions : [];

        } catch (\Exception $e) {
            Log::error('🔥 Exception in getUserTransactions', [
                'cyclos_user_id' => $cyclosUserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Format transactions for display
     */
    public function formatTransactions(array $rawTransactions): array
    {
        $formatted = [];

        foreach ($rawTransactions as $transaction) {
            $amount = floatval($transaction['amount'] ?? 0);
            
            // Determine transaction type
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

            // Format date
            $date = 'N/A';
            if (isset($transaction['date'])) {
                try {
                    $date = date('M d, Y H:i', strtotime($transaction['date']));
                } catch (\Exception $e) {
                    $date = $transaction['date'];
                }
            }

            // Get description
            $description = $transaction['description'] ?? '';
            if (empty($description)) {
                $description = $transaction['type']['name'] ?? 'Transaction';
            }

            // Get reference
            $reference = $transaction['transactionNumber'] ?? $transaction['id'] ?? 'N/A';

            $formatted[] = [
                'id' => $transaction['id'] ?? null,
                'date' => $date,
                'description' => $description,
                'amount' => abs($amount),
                'type' => $type,
                'reference' => $reference,
                'status' => $transaction['status'] ?? 'completed'
            ];
        }

        // Sort by date descending (newest first)
        usort($formatted, function($a, $b) {
            if ($a['date'] === 'N/A' || $b['date'] === 'N/A') return 0;
            return strtotime($b['date']) - strtotime($a['date']);
        });

        Log::info('📊 Formatted transactions', [
            'count' => count($formatted)
        ]);

        return $formatted;
    }

    /**
     * Get user's accounts
     */
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
                    'user_id' => $cyclosUserId,
                    'status' => $response->status()
                ]);
                return [];
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Exception in getUserAccounts', [
                'user_id' => $cyclosUserId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Add funds to user's Cyclos wallet
     */
    public function addFunds(string $userCyclosId, float $amount, string $description = ''): array
    {
        try {
            $paymentData = [
                'subject' => $userCyclosId,
                'amount' => $amount,
                'type' => $this->paymentTypeId,
                'description' => $description,
                'currency' => 'TT',
            ];

            Log::info('💰 Adding funds to Cyclos wallet', [
                'to_user' => $userCyclosId,
                'amount' => $amount
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
                Log::error('❌ Cyclos payment failed', [
                    'status' => $response->status(),
                    'error' => $response->json()
                ]);
                return ['success' => false, 'message' => 'Payment failed'];
            }

            $data = $response->json();
            Cache::forget("cyclos_balance_{$userCyclosId}");

            Log::info('✅ Cyclos payment successful', [
                'transaction_id' => $data['id'] ?? null
            ]);

            return [
                'success' => true,
                'transaction_id' => $data['id'] ?? null,
                'data' => $data
            ];

        } catch (\Exception $e) {
            Log::error('🔥 Exception in addFunds', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Get single transaction details
     */
    public function getTransaction(string $transactionId): ?array
    {
        try {
            $url = $this->baseUrl . "/api/transactions/{$transactionId}";
            
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->get($url);

            if ($response->failed()) {
                Log::error('Failed to fetch transaction', [
                    'transaction_id' => $transactionId,
                    'status' => $response->status()
                ]);
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Exception in getTransaction', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}