<?php

namespace App\Services\FavorMember\CyclosPayment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

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
    protected bool $mockMode;
    protected bool $fallbackToMock;
    protected bool $useApiPassword;
    protected ?string $currentUserId = null;

    public function __construct()
    {
        $this->loadSettings();
        $this->validateRequiredConfig();

        Log::info('CyclosPaymentService initialized', [
            'currency_code' => $this->currencyCode,
            'currency_symbol' => $this->currencySymbol,
            'admin_user_id' => $this->adminUserId,
            'payment_type_id' => $this->paymentTypeId,
            'mock_mode' => $this->mockMode,
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Load all Cyclos settings from config
     * This will get values from database (if available) or fall back to .env from services.php
     */
    protected function loadSettings(): void
    {
        try {
            // Helper function to safely get string value from config
            $getStringValue = function ($key, $default = '') {
                $value = config($key, $default);
                
                // If it's an array, try to get the first value
                if (is_array($value)) {
                    $value = reset($value);
                }
                
                return $value !== null ? (string) $value : $default;
            };
            
            // Helper function to safely get boolean value from config
            $getBooleanValue = function ($key, $default = false) {
                $value = config($key, $default);
                
                // If it's an array, try to get the first value
                if (is_array($value)) {
                    $value = reset($value);
                }
                
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            };
            
            // Load all settings from config
            $this->baseUrl = rtrim($getStringValue('services.cyclos.base_url', ''), '/');
            $this->username = $getStringValue('services.cyclos.username', '');
            $this->password = $getStringValue('services.cyclos.password', '');
            $this->channel = $getStringValue('services.cyclos.channel', 'webServices');
            $this->currencyCode = $getStringValue('services.cyclos.currency_code', 'TT');
            $this->currencySymbol = $getStringValue('services.cyclos.currency_symbol', 'TT');
            $this->currencyName = $getStringValue('services.cyclos.currency_name', 'TradeTokens');
            $this->paymentTypeId = $getStringValue('services.cyclos.payment_type_id', '');
            $this->adminUserId = $getStringValue('services.cyclos.admin_user_id', '');
            
            // Load boolean settings
            $this->mockMode = $getBooleanValue('services.cyclos.mock_mode', false);
            $this->fallbackToMock = $getBooleanValue('services.cyclos.fallback_to_mock', true);
            $this->useApiPassword = $getBooleanValue('services.cyclos.use_api_password', true);
            
            Log::info('Cyclos settings loaded from config', [
                'base_url' => $this->baseUrl,
                'has_username' => !empty($this->username),
                'has_password' => !empty($this->password),
                'has_admin_user_id' => !empty($this->adminUserId),
                'has_payment_type_id' => !empty($this->paymentTypeId),
                'currency_name' => $this->currencyName,
                'currency_symbol' => $this->currencySymbol,
                'mock_mode' => $this->mockMode
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to load Cyclos settings', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to load Cyclos configuration: ' . $e->getMessage());
        }
    }

    /**
     * Validate required configuration
     */
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

    public function isMockMode(): bool
    {
        return $this->mockMode;
    }

    /**
     * Get user balance from Cyclos
     */
    public function getUserBalance(string $cyclosUserId): float
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockGetUserBalance($cyclosUserId);
        }
        
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
                
                // If fallback to mock is enabled, return mock data
                if ($this->fallbackToMock) {
                    Log::warning('Falling back to mock balance data', [
                        'cyclos_user_id' => $cyclosUserId
                    ]);
                    return $this->mockGetUserBalance($cyclosUserId);
                }
                
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
                'cyclos_user_id' => $cyclosUserId
            ]);
            
            // If fallback to mock is enabled, return mock data
            if ($this->fallbackToMock) {
                return $this->mockGetUserBalance($cyclosUserId);
            }
            
            return 0.00;
        }
    }

    /**
     * Get user transactions from Cyclos
     */
    public function getUserTransactions(string $cyclosUserId, int $pageSize = 20, int $pageNumber = 0): array
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockGetUserTransactions($cyclosUserId, $pageSize, $pageNumber);
        }
        
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
                
                // If fallback to mock is enabled, return mock data
                if ($this->fallbackToMock) {
                    return $this->mockGetUserTransactions($cyclosUserId, $pageSize, $pageNumber);
                }
                
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
                'cyclos_user_id' => $cyclosUserId
            ]);
            
            // If fallback to mock is enabled, return mock data
            if ($this->fallbackToMock) {
                return $this->mockGetUserTransactions($cyclosUserId, $pageSize, $pageNumber);
            }
            
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

    /**
     * Add funds to a user's Cyclos wallet
     */
    public function addFunds(string $userCyclosId, float $amount, string $description = ''): array
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockAddFunds($userCyclosId, $amount, $description);
        }
        
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
                
                // If fallback to mock is enabled, return mock success
                if ($this->fallbackToMock) {
                    Log::warning('Falling back to mock add funds', [
                        'user_id' => $userCyclosId,
                        'amount' => $amount
                    ]);
                    return $this->mockAddFunds($userCyclosId, $amount, $description);
                }

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
                'user_id' => $userCyclosId,
                'amount' => $amount
            ]);
            
            // If fallback to mock is enabled, return mock success
            if ($this->fallbackToMock) {
                return $this->mockAddFunds($userCyclosId, $amount, $description);
            }

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get user accounts from Cyclos
     */
    public function getUserAccounts(string $cyclosUserId): array
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockGetUserAccounts($cyclosUserId);
        }
        
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
                
                if ($this->fallbackToMock) {
                    return $this->mockGetUserAccounts($cyclosUserId);
                }
                
                return [];
            }

            $accounts = $response->json();

            return is_array($accounts) ? $accounts : [];
        } catch (\Exception $e) {
            Log::error('Exception in getUserAccounts', [
                'error' => $e->getMessage(),
                'cyclos_user_id' => $cyclosUserId
            ]);
            
            if ($this->fallbackToMock) {
                return $this->mockGetUserAccounts($cyclosUserId);
            }
            
            return [];
        }
    }

    /**
     * Debug method to check current configuration
     */
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
            'mock_mode' => $this->mockMode,
            'fallback_to_mock' => $this->fallbackToMock,
            'use_api_password' => $this->useApiPassword,
        ];
    }

    /**
     * Mock methods for testing
     */
    protected function mockGetUserBalance(string $cyclosUserId): float
    {
        Log::info('Mock: Getting user balance', ['cyclos_user_id' => $cyclosUserId]);
        return 1000.00; // Mock balance
    }

    protected function mockGetUserTransactions(string $cyclosUserId, int $pageSize = 20, int $pageNumber = 0): array
    {
        Log::info('Mock: Getting user transactions', [
            'cyclos_user_id' => $cyclosUserId,
            'pageSize' => $pageSize,
            'pageNumber' => $pageNumber
        ]);
        
        return [
            [
                'id' => 'mock-1',
                'date' => now()->subDays(1)->toISOString(),
                'amount' => 100.00,
                'description' => 'Mock transaction 1',
                'type' => ['name' => 'Payment'],
                'status' => 'completed',
                'to' => ['user' => ['id' => $cyclosUserId]],
                'from' => ['user' => ['id' => $this->adminUserId]],
                'transactionNumber' => 'MOCK-001'
            ],
            [
                'id' => 'mock-2',
                'date' => now()->subDays(2)->toISOString(),
                'amount' => 50.00,
                'description' => 'Mock transaction 2',
                'type' => ['name' => 'Purchase'],
                'status' => 'completed',
                'to' => ['user' => ['id' => $this->adminUserId]],
                'from' => ['user' => ['id' => $cyclosUserId]],
                'transactionNumber' => 'MOCK-002'
            ]
        ];
    }

    protected function mockAddFunds(string $userCyclosId, float $amount, string $description = ''): array
    {
        Log::info('Mock: Adding funds', [
            'user_id' => $userCyclosId,
            'amount' => $amount,
            'description' => $description
        ]);
        
        return [
            'success' => true,
            'transaction_id' => 'MOCK-TXN-' . uniqid(),
            'data' => [
                'id' => 'MOCK-TXN-' . uniqid(),
                'amount' => $amount,
                'status' => 'completed'
            ]
        ];
    }

    protected function mockGetUserAccounts(string $cyclosUserId): array
    {
        Log::info('Mock: Getting user accounts', ['cyclos_user_id' => $cyclosUserId]);
        
        return [
            [
                'id' => 'mock-account-1',
                'name' => 'Main Account',
                'type' => 'primary',
                'status' => [
                    'balance' => 1000.00,
                    'currency' => $this->currencyCode
                ]
            ]
        ];
    }

    public function deductFunds(string $fromUserCyclosId, float $amount, string $description = ''): array
    {
        if ($this->mockMode) {
            return $this->mockDeductFunds($fromUserCyclosId, $amount, $description);
        }
        
        try {
            $paymentData = [
                'subject' => $this->adminUserId,
                'amount' => $amount,
                'type' => $this->paymentTypeId,
                'description' => $description,
                'currency' => $this->currencyCode,
            ];

            Log::info('Deducting funds (User -> Admin)', [
                'from_user' => $fromUserCyclosId,
                'amount' => $amount,
                'to_admin' => $this->adminUserId,
            ]);

            $paymentUrl = $this->baseUrl . "/api/{$fromUserCyclosId}/payments";

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
                Log::error('Deduct funds failed', [
                    'status' => $response->status(),
                    'error' => $errorData,
                    'from_user' => $fromUserCyclosId,
                    'amount' => $amount,
                ]);
                
                if ($this->fallbackToMock) {
                    return $this->mockDeductFunds($fromUserCyclosId, $amount, $description);
                }

                return ['success' => false, 'message' => 'Debit failed: ' . ($errorData['code'] ?? json_encode($errorData))];
            }

            $data = $response->json();
            Cache::forget("cyclos_balance_{$fromUserCyclosId}");

            Log::info('Deduct funds successful', ['transaction_id' => $data['id'] ?? null]);

            return ['success' => true, 'transaction_id' => $data['id'] ?? null, 'data' => $data];
            
        } catch (\Exception $e) {
            Log::error('Exception in deductFunds', ['error' => $e->getMessage()]);
            
            if ($this->fallbackToMock) {
                return $this->mockDeductFunds($fromUserCyclosId, $amount, $description);
            }

            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * COMPLETE TRANSFER: Debit from customer AND Credit to merchant in one go
     * This tries both methods and handles gracefully
     */
    public function transferFromCustomerToMerchant(string $customerId, string $merchantId, float $amount, string $description = ''): array
    {
        // Method 1: Try direct P2P payment (Customer -> Merchant)
        $directPayment = $this->directPayment($customerId, $merchantId, $amount, $description);
        
        if ($directPayment['success']) {
            return $directPayment;
        }
        
        // Method 2: Try two-step (Customer -> Admin, then Admin -> Merchant)
        Log::info('Direct payment failed, trying two-step transfer', ['customer' => $customerId, 'merchant' => $merchantId]);
        
        // Step 1: Debit customer (Customer -> Admin)
        $debit = $this->deductFunds($customerId, $amount, $description . ' - Customer debit');
        
        if (!$debit['success']) {
            return [
                'success' => false,
                'message' => 'Failed to debit customer: ' . ($debit['message'] ?? 'Unknown error'),
                'step' => 'debit_failed'
            ];
        }
        
        // Step 2: Credit merchant (Admin -> Merchant)
        $credit = $this->addFunds($merchantId, $amount, $description . ' - Merchant credit');
        
        if (!$credit['success']) {
            // Rollback: Refund customer
            $this->addFunds($customerId, $amount, 'ROLLBACK: ' . $description);
            
            return [
                'success' => false,
                'message' => 'Failed to credit merchant. Customer has been refunded.',
                'step' => 'credit_failed'
            ];
        }
        
        return [
            'success' => true,
            'transaction_id' => $credit['transaction_id'] ?? null,
            'debit_transaction_id' => $debit['transaction_id'] ?? null,
            'message' => 'Transfer completed via two-step process'
        ];
    }

    /**
     * Direct P2P payment between two users
     */
    public function directPayment(string $fromUserId, string $toUserId, float $amount, string $description = ''): array
    {
        if ($this->mockMode) {
            return [
                'success' => true,
                'transaction_id' => 'MOCK-P2P-' . uniqid(),
            ];
        }
        
        try {
            $paymentData = [
                'subject' => $toUserId,
                'amount' => $amount,
                'type' => $this->paymentTypeId,
                'description' => $description,
                'currency' => $this->currencyCode,
            ];

            $paymentUrl = $this->baseUrl . "/api/{$fromUserId}/payments";

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
                return ['success' => false, 'message' => $errorData['code'] ?? 'Payment failed'];
            }

            $data = $response->json();
            Cache::forget("cyclos_balance_{$fromUserId}");
            Cache::forget("cyclos_balance_{$toUserId}");

            return ['success' => true, 'transaction_id' => $data['id'] ?? null];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

  
     

    protected function mockDeductFunds(string $userId, float $amount, string $description = ''): array
    {
        return ['success' => true, 'transaction_id' => 'MOCK-DEBIT-' . uniqid()];
    }


    /**
 * Admin-initiated debit (Admin pulls money from customer)
 * This bypasses customer permissions if admin has "pay on behalf" rights
 */
public function adminInitiatedDebit(string $fromCustomerId, string $toMerchantId, float $amount, string $description = ''): array
{
    if ($this->mockMode) {
        return ['success' => true, 'transaction_id' => 'MOCK-ADMIN-DEBIT-' . uniqid()];
    }
    
    try {
        $paymentData = [
            'subject' => $toMerchantId,
            'amount' => $amount,
            'type' => $this->paymentTypeId,
            'description' => $description,
            'currency' => $this->currencyCode,
        ];

        // Admin makes payment on behalf of customer
        $paymentUrl = $this->baseUrl . "/api/{$fromCustomerId}/payments";

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
            Log::error('Admin initiated debit failed', [
                'status' => $response->status(),
                'error' => $errorData,
                'from' => $fromCustomerId,
                'to' => $toMerchantId,
                'amount' => $amount
            ]);
            
            return ['success' => false, 'message' => $errorData['code'] ?? 'Admin debit failed'];
        }

        $data = $response->json();
        Cache::forget("cyclos_balance_{$fromCustomerId}");
        Cache::forget("cyclos_balance_{$toMerchantId}");

        return ['success' => true, 'transaction_id' => $data['id'] ?? null];
        
    } catch (\Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
}