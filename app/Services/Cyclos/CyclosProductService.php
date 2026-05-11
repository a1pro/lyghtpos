<?php

namespace App\Services\Cyclos;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CyclosProductService
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
    protected ?array $marketplaceData = null;

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

            Log::info('MerchantProductService settings loaded from config', [
                'base_url' => $this->baseUrl,
                'username' => $this->username,
                'has_password' => !empty($this->password),
                'admin_user_id' => $this->adminUserId,
                'currency' => $this->currencyName,
                'mock_mode' => $this->mockMode
            ]);
        } catch (Exception $e) {
            Log::error('Failed to load MerchantProductService settings', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to load MerchantProductService configuration: ' . $e->getMessage());
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
     * Get all products for a user from Cyclos marketplace
     */
    public function getUserProducts(string $cyclosUserId, array $params = []): array
    {
        try {
            // Build URL with the correct endpoint
            $url = $this->baseUrl . "/api/{$cyclosUserId}/marketplace";

            // Prepare query parameters
            $queryParams = [];

            // Add pagination parameters if provided
            if (isset($params['pageSize'])) {
                $queryParams['pageSize'] = $params['pageSize'];
            }

            if (isset($params['page'])) {
                $queryParams['page'] = $params['page'];
            }

            // Add search/filter parameters
            if (!empty($params['keywords'])) {
                $queryParams['keywords'] = $params['keywords'];
            }

            if (!empty($params['category'])) {
                $queryParams['category'] = $params['category'];
            }

            if (!empty($params['kind'])) {
                $queryParams['kind'] = $params['kind'];
            }

            Log::info('Fetching user products from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'url' => $url,
                'query_params' => $queryParams,
            ]);

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->get($url, $queryParams);

            if ($response->failed()) {
                Log::error('Failed to fetch Cyclos products', [
                    'cyclos_user_id' => $cyclosUserId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['products' => [], 'headers' => []];
            }

            $responseData = $response->json();

            // Extract pagination info from headers
            $currentPage = (int) ($response->header('X-Current-Page') ?: 0);
            $pageSize = (int) ($response->header('X-Page-Size') ?: 20);
            $totalCount = (int) ($response->header('X-Total-Count') ?: 0);
            $pageCount = (int) ($response->header('X-Page-Count') ?: 0);
            $hasNextPage = filter_var($response->header('X-Has-Next-Page'), FILTER_VALIDATE_BOOLEAN);

            Log::info('Cyclos API response received', [
                'pagination' => compact('currentPage', 'pageSize', 'totalCount', 'pageCount', 'hasNextPage')
            ]);

            // Helper function to add quantity field and format product
            $formatProduct = function ($product) {
                if (!is_array($product)) {
                    return $product;
                }

                // Add quantity field based on stock information
                if (isset($product['unlimitedStock']) && $product['unlimitedStock'] === true) {
                    $product['quantity'] = 1000000;
                } elseif (isset($product['stockQuantity'])) {
                    $product['quantity'] = (float) $product['stockQuantity'];
                } else {
                    $product['quantity'] = 1000000;
                }

                // Ensure we have an ID field
                if (!isset($product['id']) && isset($product['_id'])) {
                    $product['id'] = $product['_id'];
                }

                return $product;
            };

            $products = [];

            // Handle different response formats based on actual API response
            if (is_array($responseData)) {
                // Check if it's a paginated response with 'items' key
                if (isset($responseData['items']) && is_array($responseData['items'])) {
                    $products = array_map($formatProduct, $responseData['items']);
                    Log::info('Products found in items key', ['count' => count($products)]);
                }
                // Check if it's a paginated response with 'data' key
                elseif (isset($responseData['data']) && is_array($responseData['data'])) {
                    $products = array_map($formatProduct, $responseData['data']);
                    Log::info('Products found in data key', ['count' => count($products)]);
                }
                // Check if it's a simple array of products
                elseif (array_keys($responseData) === range(0, count($responseData) - 1)) {
                    $products = array_map($formatProduct, $responseData);
                    Log::info('Products found as simple array', ['count' => count($products)]);
                }
                // Check if it's a single product object
                elseif (isset($responseData['id']) || isset($responseData['_id'])) {
                    $products = [$formatProduct($responseData)];
                    Log::info('Single product found', ['id' => $responseData['id'] ?? $responseData['_id'] ?? 'unknown']);
                }
                // If none of the above, log the structure and return empty
                else {
                    Log::warning('Unexpected response format from Cyclos API', [
                        'structure' => array_keys($responseData),
                        'sample' => json_encode(array_slice($responseData, 0, 1))
                    ]);
                    $products = [];
                }
            }

            return [
                'products' => $products,
                'pagination' => [
                    'current_page' => $currentPage + 1,
                    'per_page' => $pageSize,
                    'total' => $totalCount,
                    'total_pages' => $pageCount,
                    'has_next_page' => $hasNextPage,
                    'has_prev_page' => $currentPage > 0,
                ]
            ];
        } catch (Exception $e) {
            Log::error('Exception in getUserProducts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'products' => [],
                'pagination' => []
            ];
        }
    }

    /**
     * Search products with filters
     */
    public function searchProducts(string $cyclosUserId, string $searchTerm = '', array $filters = []): array
    {
        $params = [];

        if (!empty($searchTerm)) {
            $params['keywords'] = $searchTerm;
        }

        if (!empty($filters['category'])) {
            $params['category'] = $filters['category'];
        }

        if (!empty($filters['kind'])) {
            $params['kind'] = $filters['kind'];
        }

        $params['pageSize'] = $filters['per_page'];
        $params['page'] = $filters['page'];

        return $this->getUserProducts($cyclosUserId, $params);
    }

    /**
     * Format Cyclos product to match the expected format for views
     */
    public function formatProduct(array $rawProduct): array
    {
        $imageUrl = null;

        if (isset($rawProduct['image']) && is_array($rawProduct['image']) && isset($rawProduct['image']['url'])) {
            $imageUrl = $rawProduct['image']['url'];
        } elseif (isset($rawProduct['images']) && is_array($rawProduct['images']) && !empty($rawProduct['images'])) {
            $firstImage = $rawProduct['images'][0];

            if (is_array($firstImage) && isset($firstImage['url'])) {
                $imageUrl = $firstImage['url'];
            } elseif (is_string($firstImage)) {
                $imageUrl = $this->baseUrl . "/api/files/{$firstImage}/data";
            }
        }

        $customValues = $rawProduct['customValues'] ?? [];
        $barcode = $customValues['barcode'] ?? 'N/A';

        // Get quantity - check both possible field names
        $quantity = 0;
        if (isset($rawProduct['stockQuantity'])) {
            $quantity = (int) $rawProduct['stockQuantity'];
        } elseif (isset($rawProduct['quantity'])) {
            $quantity = (int) $rawProduct['quantity'];
        }

        // Get price
        $price = (float) ($rawProduct['price'] ?? 0);

        // Get creation date
        $createdAt = $rawProduct['creationDate'] ?? $rawProduct['createdAt'] ?? $rawProduct['publicationPeriod']['begin'] ?? null;
        $updatedAt = $rawProduct['lastModifiedDate'] ?? $rawProduct['updatedAt'] ?? $rawProduct['publicationPeriod']['end'] ?? null;

        // Get currency symbol
        $currencySymbol = $this->getCurrencySymbol($rawProduct['currency'] ?? 'unit');

        return [
            'id' => $rawProduct['id'] ?? $rawProduct['_id'] ?? null,
            'name' => $rawProduct['name'] ?? 'Unnamed Product',
            'description' => $rawProduct['description'] ?? '',
            'barcode' => $barcode,
            'product_number' => $rawProduct['productNumber'] ?? 'N/A',
            'price' => number_format($price, 2),
            'price_raw' => $price,
            'quantity' => $quantity,
            'stock_quantity' => isset($rawProduct['stockQuantity']) ? (float) $rawProduct['stockQuantity'] : $quantity,
            'unlimited_stock' => (bool) ($rawProduct['unlimitedStock'] ?? false),
            'status' => $this->determineProductStatus($rawProduct),
            'image_url' => $imageUrl,
            'image' => $rawProduct['image'] ?? null,
            'categories' => $rawProduct['categories'] ?? [],
            'currency' => $rawProduct['currency'] ?? 'unit',
            'currency_symbol' => $currencySymbol,
            'kind' => $rawProduct['kind'] ?? 'webshop',
            'publication_period' => $rawProduct['publicationPeriod'] ?? null,
            'user' => $rawProduct['user'] ?? null,
            'editable' => $rawProduct['editable'] ?? false,
            'can_add_to_cart' => $rawProduct['canAddToCart'] ?? false,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'raw' => $rawProduct,
        ];
    }

    /**
     * Get currency symbol based on currency code or currency array
     */
    private function getCurrencySymbol($currency): string
    {
        // If it's an array, extract the internalName from the first element
        if (is_array($currency)) {
            // If it's a nested array like currency[0]['internalName']
            if (isset($currency[0]) && is_array($currency[0]) && isset($currency[0]['internalName'])) {
                $currencyCode = $currency[0]['internalName'];
            }
            // If it's an associative array with 'internalName' key
            elseif (isset($currency['internalName'])) {
                $currencyCode = $currency['internalName'];
            }
            // If it's a simple array of currency codes
            elseif (!empty($currency)) {
                $currencyCode = $currency[0];
            } else {
                $currencyCode = 'unit'; // default fallback
            }
        }
        // If it's a string, use it directly
        else {
            $currencyCode = $currency;
        }

        // Map currency codes to display symbols
        $symbols = [
            'trade_tokens' => 'TT',
            'unit' => 'CC',
            'fvt' => 'FT',
            'cannabucks' => 'Cannabucks',
            'charity_token' => 'CharityToken',
            'lead_token' => 'LeadToken',
            'reward_tokens' => 'RewardTokens',
            'vv_tokens' => 'Voucher Value Tokens',
            'usd' => '$',
            'eur' => '€',
            'gbp' => '£',
            'inr' => '₹',
        ];

        return $symbols[$currencyCode] ?? $currencyCode;
    }

    /**
     * Format a collection of Cyclos products
     */
    public function formatProducts(array $rawProducts): array
    {
        $formatted = [];

        foreach ($rawProducts as $product) {
            $formatted[] = $this->formatProduct($product);
        }

        return $formatted;
    }

    /**
     * Determine product status based on publication period and stock
     */
    protected function determineProductStatus(array $product): bool
    {
        // Check if product is within publication period
        $publicationPeriod = $product['publicationPeriod'] ?? null;
        if ($publicationPeriod) {
            $now = now();

            // Parse begin date
            $begin = null;
            if (isset($publicationPeriod['begin'])) {
                try {
                    $begin = now()->parse($publicationPeriod['begin']);
                } catch (\Exception $e) {
                    Log::warning('Failed to parse begin date', ['date' => $publicationPeriod['begin']]);
                }
            }

            // Parse end date
            $end = null;
            if (isset($publicationPeriod['end'])) {
                try {
                    $end = now()->parse($publicationPeriod['end']);
                } catch (\Exception $e) {
                    Log::warning('Failed to parse end date', ['date' => $publicationPeriod['end']]);
                }
            }

            // If begin date is in the future, product is not yet active
            if ($begin && $now < $begin) {
                return false;
            }

            // If end date is in the past, product is expired
            if ($end && $now > $end) {
                return false;
            }
        }

        // Check stock
        $unlimitedStock = (bool) ($product['unlimitedStock'] ?? false);
        $stockQuantity = (int) ($product['stockQuantity'] ?? 0);

        if (!$unlimitedStock && $stockQuantity <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Get minimal categories data (only id, name, and children with their ids)
     */
    public function getMinimalCategories(string $cyclosUserId): array
    {
        try {
            // Build URL with the correct endpoint
            $url = $this->baseUrl . "/api/{$cyclosUserId}/marketplace/data-for-search?kind=webshop";

            Log::info('Fetching categories from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'url' => $url,
            ]);

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->get($url);

            if ($response->failed()) {
                Log::error('Failed to fetch categories from Cyclos', [
                    'cyclos_user_id' => $cyclosUserId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $responseData = $response->json();

            // Extract and transform only the needed data
            if (!isset($responseData['categories']) || !is_array($responseData['categories'])) {
                return [];
            }

            return $this->transformCategories($responseData['categories']);
        } catch (Exception $e) {
            Log::error('Exception in getMinimalCategories', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    /**
     * Transform categories to minimal format (only id, name, and children)
     */
    private function transformCategories(array $categories): array
    {
        $transformed = [];

        foreach ($categories as $category) {
            $transformedCategory = [
                'id' => $category['id'],
                'name' => $category['name'],
            ];

            // Add children if they exist
            if (isset($category['children']) && is_array($category['children']) && !empty($category['children'])) {
                $transformedCategory['children'] = $this->transformChildren($category['children']);
            } else {
                $transformedCategory['children'] = [];
            }

            $transformed[] = $transformedCategory;
        }

        return $transformed;
    }

    /**
     * Transform children categories recursively
     */
    private function transformChildren(array $children): array
    {
        $transformed = [];

        foreach ($children as $child) {
            $transformedChild = [
                'id' => $child['id'],
                'name' => $child['name'],
            ];

            // Recursively handle nested children if any
            if (isset($child['children']) && is_array($child['children']) && !empty($child['children'])) {
                $transformedChild['children'] = $this->transformChildren($child['children']);
            } else {
                $transformedChild['children'] = [];
            }

            $transformed[] = $transformedChild;
        }

        return $transformed;
    }
}
