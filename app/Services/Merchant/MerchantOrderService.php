<?php

namespace App\Services\Merchant;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
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

    public function __construct()
    {
        $this->loadSettingsFromDatabase();
    }

    /**
     * Load settings from database
     */
    protected function loadSettingsFromDatabase(): void
    {
        try {
            // Load base URL
            $baseUrlSetting = AppSetting::where('group', 'cyclos')
                ->where('key', 'CYCLOS_BASE_URL')
                ->first();
            $this->baseUrl = rtrim($baseUrlSetting ? $baseUrlSetting->value : '', '/');

            // Load API username
            $usernameSetting = AppSetting::where('group', 'cyclos')
                ->where('key', 'CYCLOS_API_USERNAME')
                ->first();
            $this->username = $usernameSetting ? $usernameSetting->value : '';

            // Load API password
            $passwordSetting = AppSetting::where('group', 'cyclos')
                ->where('key', 'CYCLOS_API_PASSWORD')
                ->first();
            $this->password = $passwordSetting ? $passwordSetting->value : '';

            // Load channel
            $this->channel = 'webServices';

            // Load admin user ID
            $adminUserIdSetting = AppSetting::where('group', 'cyclos')
                ->where('key', 'CYCLOS_ADMIN_USER_ID')
                ->first();
            $this->adminUserId = $adminUserIdSetting ? $adminUserIdSetting->value : '';

            // Load currency settings
            $currencyCodeSetting = AppSetting::where('group', 'cyclos')
                ->where('key', 'CYCLOS_CURRENCY_CODE')
                ->first();
            $this->currencyCode = $currencyCodeSetting ? $currencyCodeSetting->value : 'TT';

            $currencySymbolSetting = AppSetting::where('group', 'cyclos')
                ->where('key', 'CYCLOS_CURRENCY_SYMBOL')
                ->first();
            $this->currencySymbol = $currencySymbolSetting ? $currencySymbolSetting->value : 'TT';

            $currencyNameSetting = AppSetting::where('group', 'cyclos')
                ->where('key', 'CYCLOS_CURRENCY_NAME')
                ->first();
            $this->currencyName = $currencyNameSetting ? $currencyNameSetting->value : 'TradeTokens';

            Log::info('MerchantOrderService settings loaded from database', [
                'base_url' => $this->baseUrl,
                'username' => $this->username,
                'has_password' => !empty($this->password),
                'admin_user_id' => $this->adminUserId,
                'currency' => $this->currencyName
            ]);

            $this->validateRequiredConfig();

        } catch (Exception $e) {
            Log::error('Failed to load MerchantOrderService settings', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function validateRequiredConfig(): void
    {
        $missing = [];

        if (empty($this->baseUrl)) {
            $missing[] = 'CYCLOS_BASE_URL';
        }

        if (empty($this->username)) {
            $missing[] = 'CYCLOS_API_USERNAME';
        }

        if (empty($this->password)) {
            $missing[] = 'CYCLOS_API_PASSWORD';
        }

        if (empty($this->adminUserId)) {
            $missing[] = 'CYCLOS_ADMIN_USER_ID';
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
            'items' => []
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
}