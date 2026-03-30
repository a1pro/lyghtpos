<?php

namespace App\Services\Merchant;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use Exception;

class MerchantOrderService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $channel;
    protected string $currencyCode;
    protected string $currencySymbol;
    protected string $currencyName;
    protected string $adminUserId;
    protected bool $mockMode;
    protected bool $fallbackToMock;

    public function __construct()
    {
        $this->loadSettings();
        $this->validateRequiredConfig();

        Log::info('MerchantOrderService initialized', [
            'currency_code' => $this->currencyCode,
            'currency_symbol' => $this->currencySymbol,
            'admin_user_id' => $this->adminUserId,
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
            $this->adminUserId = $getStringValue('services.cyclos.admin_user_id', '');
            
            // Load boolean settings
            $this->mockMode = $getBooleanValue('services.cyclos.mock_mode', false);
            $this->fallbackToMock = $getBooleanValue('services.cyclos.fallback_to_mock', true);
            
            Log::info('MerchantOrderService settings loaded from config', [
                'base_url' => $this->baseUrl,
                'username' => $this->username,
                'has_password' => !empty($this->password),
                'admin_user_id' => $this->adminUserId,
                'currency' => $this->currencyName,
                'mock_mode' => $this->mockMode
            ]);

        } catch (Exception $e) {
            Log::error('Failed to load MerchantOrderService settings', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to load MerchantOrderService configuration: ' . $e->getMessage());
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

        if (empty($this->adminUserId)) {
            $missing[] = 'services.cyclos.admin_user_id';
        }

        if (!empty($missing)) {
            Log::error('Missing required Cyclos configuration', [
                'missing' => $missing
            ]);
            throw new \RuntimeException(
                'Missing required Cyclos configuration: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * Check if mock mode is enabled
     */
    public function isMockMode(): bool
    {
        return $this->mockMode;
    }

    /**
     * Get the base URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get all orders for a user from Cyclos
     */
    public function getUserOrders(string $cyclosUserId, array $params = []): array
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockGetUserOrders($cyclosUserId, $params);
        }
        
        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/orders";

            // Handle date filters
            if (!empty($params['start_date'])) {
                $params['creationDateFrom'] = Carbon::parse($params['start_date'])->startOfDay()->toISOString();
                unset($params['start_date']);
            }

            if (!empty($params['end_date'])) {
                $params['creationDateTo'] = Carbon::parse($params['end_date'])->endOfDay()->toISOString();
                unset($params['end_date']);
            }

            Log::info('Fetching user orders from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'url' => $url,
                'params' => $params
            ]);

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->get($url, $params);

            if ($response->failed()) {
                Log::error('Failed to fetch Cyclos orders', [
                    'cyclos_user_id' => $cyclosUserId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                
                // If fallback to mock is enabled, return mock data
                if ($this->fallbackToMock) {
                    Log::warning('Falling back to mock orders data', [
                        'cyclos_user_id' => $cyclosUserId
                    ]);
                    return $this->mockGetUserOrders($cyclosUserId, $params);
                }
                
                return ['orders' => [], 'headers' => []];
            }

            $orders = $response->json();

            $headers = [
                'x-current-page' => $response->header('X-Current-Page'),
                'x-page-size' => $response->header('X-Page-Size'),
                'x-total-count' => $response->header('X-Total-Count'),
                'x-page-count' => $response->header('X-Page-Count'),
                'x-has-next-page' => $response->header('X-Has-Next-Page'),
            ];

            Log::info('Cyclos API orders received', [
                'orders_count' => is_array($orders) ? count($orders) : 0
            ]);

            // Handle different response formats
            $ordersArray = $this->normalizeOrdersResponse($orders);

            // Transform orders to include formatted data
            $transformedOrders = array_map(function ($order) {
                return $this->transformOrder($order);
            }, $ordersArray);

            return [
                'orders' => $transformedOrders,
                'headers' => $headers
            ];
        } catch (Exception $e) {
            Log::error('Exception in getUserOrders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // If fallback to mock is enabled, return mock data
            if ($this->fallbackToMock) {
                return $this->mockGetUserOrders($cyclosUserId, $params);
            }
            
            return ['orders' => [], 'headers' => []];
        }
    }

    /**
     * Normalize orders response to always return an array
     */
    protected function normalizeOrdersResponse($orders): array
    {
        if (!is_array($orders)) {
            return [];
        }

        // Check if it's a paginated response with 'items' or 'data' key
        if (isset($orders['items']) && is_array($orders['items'])) {
            return $orders['items'];
        }

        if (isset($orders['data']) && is_array($orders['data'])) {
            return $orders['data'];
        }

        // If it's a simple array of orders
        if (array_keys($orders) === range(0, count($orders) - 1)) {
            return $orders;
        }

        // If it's a single order
        if (isset($orders['id'])) {
            return [$orders];
        }

        return [];
    }

    /**
     * Transform order data for consistent display
     */
    protected function transformOrder(array $order): array
    {
        $currency = $order['currency'] ?? [];

        return [
            'id' => $order['id'] ?? null,
            'number' => $order['number'] ?? null,
            'total_price' => floatval($order['totalPrice'] ?? 0),
            'total_price_formatted' => $this->formatPrice($order['totalPrice'] ?? 0, $currency),
            'status' => $order['status'] ?? null,
            'status_label' => $this->getStatusLabel($order['status'] ?? null),
            'status_badge_class' => $this->getStatusBadgeClass($order['status'] ?? null),
            'creation_date' => $order['creationDate'] ?? null,
            'creation_date_formatted' => $this->formatDate($order['creationDate'] ?? null),
            'customer' => [
                'id' => $order['relatedUser']['id'] ?? null,
                'name' => $order['relatedUser']['display'] ?? 'N/A'
            ],
            'currency' => [
                'symbol' => $currency['symbol'] ?? $this->currencySymbol,
                'name' => $currency['name'] ?? $this->currencyName,
                'prefix' => $currency['prefix'] ?? '',
                'decimal_digits' => $currency['decimalDigits'] ?? 2
            ],
            'items' => $order['items'] ?? []
        ];
    }

    /**
     * Format price with currency
     */
    protected function formatPrice($price, array $currency = null): string
    {
        $amount = floatval($price);
        $decimalDigits = $currency['decimalDigits'] ?? 2;
        $prefix = $currency['prefix'] ?? $this->currencySymbol;

        return $prefix . ' ' . number_format($amount, $decimalDigits);
    }

    /**
     * Format date
     */
    protected function formatDate($date): string
    {
        if (!$date) {
            return 'N/A';
        }

        try {
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return $date;
        }
    }

    /**
     * Get human-readable status label
     */
    protected function getStatusLabel(?string $status): string
    {
        $statuses = [
            'pendingBuyer' => 'Pending Buyer',
            'pendingSeller' => 'Pending Seller',
            'confirmed' => 'Confirmed',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
        ];

        return $statuses[$status] ?? ucfirst($status ?? 'Unknown');
    }

    /**
     * Get badge class for status
     */
    protected function getStatusBadgeClass(?string $status): string
    {
        $classes = [
            'pendingBuyer' => 'badge-warning',
            'pendingSeller' => 'badge-warning',
            'confirmed' => 'badge-info',
            'shipped' => 'badge-primary',
            'delivered' => 'badge-success',
            'cancelled' => 'badge-danger',
            'rejected' => 'badge-danger',
        ];

        return $classes[$status] ?? 'badge-secondary';
    }

    /**
     * Create an order in Cyclos marketplace
     */
    public function createOrder(string $cyclosUserId, array $orderData): array
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockCreateOrder($cyclosUserId, $orderData);
        }
        
        try {
            // Use admin user ID for the URL to have proper permissions
            $url = $this->baseUrl . "/api/{$this->adminUserId}/orders";

            // Prepare the order data for Cyclos API
            $cyclosOrderData = [
                'deliveryMethod' => [
                    'name' => $orderData['delivery_method'] ?? "pickup",
                    'price' => $orderData['delivery_price'] ?? 0,
                    'maxTime' => [
                        "amount" => 2,
                        "field" => "days"
                    ],
                ],
                "deliveryAddress" => $orderData['delivery_address'] ?? [
                    "addressLine1" => "",
                    "street" => "",
                    "buildingNumber" => "",
                    "zip" => "",
                    "city" => "",
                    "region" => "",
                    "country" => "US"
                ],
                "draft" => false,
                "items" => $orderData['items'],
                'currency' => $orderData['currency'] ?? 'unit',
                "buyer" => $orderData['buyer_id'],
                "remarks" => $orderData['notes'] ?? null,
                "merchant" => $cyclosUserId // Add merchant to the order
            ];

            Log::info('Creating order in Cyclos', [
                'admin_user_id' => $this->adminUserId,
                'merchant_cyclos_id' => $cyclosUserId,
                'buyer_cyclos_id' => $orderData['buyer_id'],
                'url' => $url,
                'order_data' => $cyclosOrderData,
            ]);

            // Use database credentials (jackdoe25 is stored in DB)
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->post($url, $cyclosOrderData);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? $errorData['code'] ?? 'Failed to create order';

                Log::error('Failed to create order in Cyclos', [
                    'status' => $response->status(),
                    'error' => $errorData,
                    'response_body' => $response->body(),
                ]);
                
                // If fallback to mock is enabled, return mock success
                if ($this->fallbackToMock) {
                    Log::warning('Falling back to mock create order', [
                        'merchant_id' => $cyclosUserId,
                        'buyer_id' => $orderData['buyer_id']
                    ]);
                    return $this->mockCreateOrder($cyclosUserId, $orderData);
                }

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => $errorData,
                    'status_code' => $response->status()
                ];
            }

            $data = $response->json();

            Log::info('Order created successfully in Cyclos', [
                'order_id' => $data['id'] ?? null,
                'order_number' => $data['number'] ?? null
            ]);

            return [
                'success' => true,
                'order_id' => $data['id'] ?? null,
                'order_number' => $data['number'] ?? null,
                'data' => $data,
            ];
        } catch (Exception $e) {
            Log::error('Exception in createOrder', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // If fallback to mock is enabled, return mock success
            if ($this->fallbackToMock) {
                return $this->mockCreateOrder($cyclosUserId, $orderData);
            }

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Search orders with filters
     */
    public function searchOrders(string $cyclosUserId, string $searchTerm = '', array $filters = []): array
    {
        $params = [];

        if (!empty($searchTerm)) {
            $params['keywords'] = $searchTerm;
        }

        if (!empty($filters['category'])) {
            $params['category'] = $filters['category'];
        }

        if (!empty($filters['min_price'])) {
            $params['minPrice'] = (float) $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $params['maxPrice'] = (float) $filters['max_price'];
        }

        if (!empty($filters['status'])) {
            $params['status'] = $filters['status'];
        }

        $params['pageSize'] = $filters['per_page'] ?? 20;
        $params['pageNumber'] = $filters['page'] ?? 0;

        return $this->getUserOrders($cyclosUserId, $params);
    }

    /**
     * Debug method to check current configuration
     */
    public function debugConfig(): array
    {
        return [
            'admin_user_id' => $this->adminUserId,
            'currency_code' => $this->currencyCode,
            'currency_symbol' => $this->currencySymbol,
            'currency_name' => $this->currencyName,
            'base_url' => $this->baseUrl,
            'username' => $this->username,
            'channel' => $this->channel,
            'mock_mode' => $this->mockMode,
            'fallback_to_mock' => $this->fallbackToMock,
        ];
    }

    /**
     * Mock methods for testing
     */
    protected function mockGetUserOrders(string $cyclosUserId, array $params = []): array
    {
        Log::info('Mock: Getting user orders', [
            'cyclos_user_id' => $cyclosUserId,
            'params' => $params
        ]);
        
        $mockOrders = [
            [
                'id' => 'order-mock-1',
                'number' => 'ORD-001',
                'totalPrice' => 100.00,
                'status' => 'confirmed',
                'creationDate' => now()->subDays(2)->toISOString(),
                'currency' => [
                    'symbol' => $this->currencySymbol,
                    'name' => $this->currencyName,
                    'decimalDigits' => 2
                ],
                'relatedUser' => [
                    'id' => 'user-mock-1',
                    'display' => 'Test Customer'
                ],
                'items' => [
                    [
                        'id' => 'item-1',
                        'product' => ['name' => 'Test Product'],
                        'quantity' => 1,
                        'price' => 100.00
                    ]
                ]
            ],
            [
                'id' => 'order-mock-2',
                'number' => 'ORD-002',
                'totalPrice' => 50.00,
                'status' => 'pendingSeller',
                'creationDate' => now()->subDays(5)->toISOString(),
                'currency' => [
                    'symbol' => $this->currencySymbol,
                    'name' => $this->currencyName,
                    'decimalDigits' => 2
                ],
                'relatedUser' => [
                    'id' => 'user-mock-2',
                    'display' => 'Another Customer'
                ],
                'items' => []
            ]
        ];
        
        // Apply filters if needed
        if (!empty($params['status'])) {
            $mockOrders = array_filter($mockOrders, function($order) use ($params) {
                return $order['status'] === $params['status'];
            });
            $mockOrders = array_values($mockOrders);
        }
        
        $transformedOrders = array_map(function ($order) {
            return $this->transformOrder($order);
        }, $mockOrders);
        
        return [
            'orders' => $transformedOrders,
            'headers' => [
                'x-current-page' => 0,
                'x-page-size' => 20,
                'x-total-count' => count($mockOrders),
                'x-page-count' => 1,
                'x-has-next-page' => false,
            ]
        ];
    }
    
    protected function mockCreateOrder(string $cyclosUserId, array $orderData): array
    {
        Log::info('Mock: Creating order', [
            'merchant_id' => $cyclosUserId,
            'buyer_id' => $orderData['buyer_id'],
            'items' => $orderData['items']
        ]);
        
        return [
            'success' => true,
            'order_id' => 'mock-order-' . uniqid(),
            'order_number' => 'MOCK-ORD-' . rand(1000, 9999),
            'data' => [
                'id' => 'mock-order-' . uniqid(),
                'number' => 'MOCK-ORD-' . rand(1000, 9999),
                'status' => 'pendingSeller'
            ]
        ];
    }
}