<?php

namespace App\Services\Cyclos;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use Exception;
use Illuminate\Validation\ValidationException;

class MerchantCyclosOrderService
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
    }

    /**
     * Load all Cyclos settings from config
     * This will get values from database (if available) or fall back to .env from services.php
     */
    protected function loadSettings(): void
    {
        try {
            $getStringValue = function ($key, $default = '') {
                $value = config($key, $default);

                if (is_array($value)) {
                    $value = reset($value);
                }

                return $value !== null ? (string) $value : $default;
            };

            $getBooleanValue = function ($key, $default = false) {
                $value = config($key, $default);

                if (is_array($value)) {
                    $value = reset($value);
                }

                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            };

            $this->baseUrl = rtrim($getStringValue('services.cyclos.base_url', ''), '/');
            $this->username = $getStringValue('services.cyclos.username', '');
            $this->password = $getStringValue('services.cyclos.password', '');
            $this->channel = $getStringValue('services.cyclos.channel', 'webServices');
            $this->currencyCode = $getStringValue('services.cyclos.currency_code', 'unit');
            $this->currencySymbol = $getStringValue('services.cyclos.currency_symbol', 'TT');
            $this->currencyName = $getStringValue('services.cyclos.currency_name', 'TradeTokens');
            $this->adminUserId = $getStringValue('services.cyclos.admin_user_id', '');

            $this->mockMode = $getBooleanValue('services.cyclos.mock_mode', false);
            $this->fallbackToMock = $getBooleanValue('services.cyclos.fallback_to_mock', true);
        } catch (Exception $e) {
            Log::error('Failed to load MerchantCyclosOrderService settings', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to load MerchantCyclosOrderService configuration: ' . $e->getMessage());
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
    public function createOrder($merchant, string $customerCyclosId, array $orderData): array
    {
        try {
            $cyclosUserId = $merchant->cyclos_user_id;

            $url = $this->baseUrl . "/api/{$cyclosUserId}/orders";

            // By default the deliveryMethod will be PICKUP - so address will be merchant's
            $merchantAddress = $merchant->address;
            if (!$merchantAddress) {
                throw ValidationException::withMessages(['address' => 'Merchant address not configured for order creation!']);
            }

            $cyclosOrderData = [
                'deliveryMethod' => [
                    'name' => "pickup",
                    'price' => 0,
                    'maxTime' => [
                        "amount" => 2,
                        "field" => "days"
                    ],
                ],
                "deliveryAddress" => [
                    "addressLine1" => $merchantAddress->address ?? null,
                    "zip" => $merchantAddress->postal_code ?? null,
                    "city" => $merchantAddress->city_name ?? null,
                    "region" => $merchantAddress->state_name ?? null,
                    "country" => $merchantAddress->countryRelation->shortname ?? null,
                ],
                "draft" => false,
                "items" => $orderData['items'],
                'currency' => $orderData['currency'] ?? $this->currencyCode,
                "buyer" => $customerCyclosId,
                "remarks" => $orderData['notes'] ?? null,
                "merchant" => $cyclosUserId
            ];

            Log::info('Creating order in Cyclos', [
                'merchant_cyclos_id' => $cyclosUserId,
                'buyer_cyclos_id' => $customerCyclosId,
                'url' => $url,
                'order_data' => $cyclosOrderData,
            ]);

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withBasicAuth($merchant->cyclos_username, $merchant->cyclos_user_password)
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
                'order_id' => $data ?? null,
            ]);

            return [
                'success' => true,
                'order_id' => $data ?? null,
                'data' => $data,
            ];
        } catch (Exception $e) {
            Log::error('Exception in createOrder', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Accept order as buyer
     */
    public function acceptOrderAsBuyer($buyer, string $orderId, array $data): array
    {
        try {
            $paymentTypeId = config('services.cyclos.order_payment_type_id');

            $cyclosUserId = $buyer->cyclos_user_id;
            $url = $this->baseUrl . "/api/orders/{$orderId}/buyer/accept";

            $payload = [
                'remarks' => $data['remarks'] ?? null,
                'paymentType' => $paymentTypeId,
            ];

            Log::info('Accepting order as buyer in Cyclos', [
                'buyer_cyclos_id' => $cyclosUserId,
                'order_id' => $orderId,
                'url' => $url,
                'payload' => $payload,
            ]);

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withBasicAuth($buyer->cyclos_username, $buyer->cyclos_user_password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->post($url, $payload);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? $errorData['code'] ?? 'Failed to accept order';

                Log::error('Failed to accept order in Cyclos', [
                    'order_id' => $orderId,
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

            $resData = $response->json();

            Log::info('Order accepted successfully in Cyclos', [
                'order_id' => $orderId,
                'response' => $resData,
            ]);

            return [
                'success' => true,
                'message' => 'Order accepted successfully',
                'data' => $resData,
            ];
        } catch (Exception $e) {
            Log::error('Exception in acceptOrderAsBuyer', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
