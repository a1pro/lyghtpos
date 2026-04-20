<?php

namespace App\Services\Merchant;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\UploadedFile;
use Exception;

class MerchantProductService
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

        Log::info('MerchantProductService initialized', [
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
     * Get all marketplace data from Cyclos
     */
    public function getMarketplaceData(): array
    {
        if ($this->marketplaceData !== null) {
            return $this->marketplaceData;
        }

        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockGetMarketplaceData();
        }

        try {
            $url = $this->baseUrl . "/api/marketplace/data-for-search?kind=webshop";

            Log::info('Fetching marketplace data from Cyclos', [
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
                Log::error('Failed to fetch marketplace data', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                if ($this->fallbackToMock) {
                    return $this->mockGetMarketplaceData();
                }

                return [];
            }

            $data = $response->json();

            Log::info('Marketplace data fetched successfully', [
                'categories_count' => count($data['categories'] ?? []),
                'currencies_count' => count($data['currencies'] ?? [])
            ]);

            Cache::put('cyclos_marketplace_data', $data, 3600);
            $this->marketplaceData = $data;

            return $data;
        } catch (Exception $e) {
            Log::error('Exception in getMarketplaceData', [
                'error' => $e->getMessage(),
            ]);

            if ($this->fallbackToMock) {
                return $this->mockGetMarketplaceData();
            }

            return [];
        }
    }

    /**
     * Get marketplace categories
     */
    public function getMarketplaceCategories(): array
    {
        $data = $this->getMarketplaceData();
        return $data['categories'] ?? [];
    }

    /**
     * Get product categories from Cyclos
     */
    public function getCategories(string $cyclosUserId): array
    {
        return $this->getMarketplaceCategories();
    }

    /**
     * Flatten categories hierarchy for select dropdown
     */
    public function flattenCategories(array $categories, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($categories as $category) {
            $id = $category['id'] ?? null;
            $name = $category['name'] ?? 'Unknown';
            $children = $category['children'] ?? [];

            if ($id) {
                $displayName = $prefix ? $prefix . ' > ' . $name : $name;
                $flattened[$id] = $displayName;

                if (!empty($children)) {
                    $flattened = $flattened + $this->flattenCategories($children, $displayName);
                }
            }
        }

        return $flattened;
    }

    /**
     * Get category internal name by ID
     */
    public function getCategoryInternalName(string $categoryId): ?string
    {
        $categories = $this->getMarketplaceCategories();
        return $this->findInternalNameById($categories, $categoryId);
    }

    /**
     * Find internal name by category ID
     */
    private function findInternalNameById(array $categories, string $searchId): ?string
    {
        foreach ($categories as $category) {
            if ((string)$category['id'] === (string)$searchId) {
                return $category['internalName'] ?? null;
            }

            if (!empty($category['children'])) {
                $found = $this->findInternalNameById($category['children'], $searchId);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Get a default category for products
     */
    public function getDefaultCategory(string $cyclosUserId): ?string
    {
        try {
            $categories = $this->getMarketplaceCategories();

            if (!empty($categories)) {
                $flattened = $this->flattenCategories($categories);
                if (!empty($flattened)) {
                    foreach ($flattened as $id => $name) {
                        return $id;
                    }
                }
            }

            return null;
        } catch (Exception $e) {
            Log::error('Error getting default category', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get trade types from marketplace data
     */
    public function getTradeTypes(): array
    {
        $data = $this->getMarketplaceData();
        $customFields = $data['customFields'] ?? [];

        foreach ($customFields as $field) {
            if (($field['internalName'] ?? '') === 'tradeType') {
                return $field['possibleValues'] ?? [];
            }
        }

        return [
            ['internalName' => 'tradeTypeOffer', 'value' => 'Offer'],
            ['internalName' => 'tradeTypeDemand', 'value' => 'Demand']
        ];
    }

    /**
     * Get currencies from marketplace data
     */
    public function getCurrencies(): array
    {
        $data = $this->getMarketplaceData();
        return $data['currencies'] ?? [];
    }

    /**
     * Get all products for a user from Cyclos marketplace
     */
    public function getUserProducts(string $cyclosUserId, array $params = []): array
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockGetUserProducts($cyclosUserId, $params);
        }

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

            if (!empty($params['minPrice'])) {
                $queryParams['minPrice'] = $params['minPrice'];
            }

            if (!empty($params['maxPrice'])) {
                $queryParams['maxPrice'] = $params['maxPrice'];
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

                if ($this->fallbackToMock) {
                    return $this->mockGetUserProducts($cyclosUserId, $params);
                }

                return ['products' => [], 'headers' => []];
            }

            $responseData = $response->json();

            // Extract pagination headers from response headers
            $headers = [
                'x-current-page' => $response->header('X-Current-Page'),
                'x-page-size' => $response->header('X-Page-Size'),
                'x-total-count' => $response->header('X-Total-Count'),
                'x-page-count' => $response->header('X-Page-Count'),
                'x-has-next-page' => $response->header('X-Has-Next-Page'),
            ];

            Log::info('Cyclos API response received', [
                'headers' => $headers,
                'response_type' => gettype($responseData),
                'is_array' => is_array($responseData),
                'response_keys' => is_array($responseData) ? array_keys($responseData) : null,
                'response_sample' => is_array($responseData) ? array_slice($responseData, 0, 2) : null
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
                'headers' => $headers
            ];
        } catch (Exception $e) {
            Log::error('Exception in getUserProducts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->fallbackToMock) {
                return $this->mockGetUserProducts($cyclosUserId, $params);
            }

            return ['products' => [], 'headers' => []];
        }
    }

    /**
     * Get a single product by ID
     */
    public function getProduct(string $cyclosUserId, string $productId): ?array
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockGetProduct($cyclosUserId, $productId);
        }

        try {
            $url = $this->baseUrl . "/api/marketplace/{$productId}";

            Log::info('Fetching single product from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'product_id' => $productId,
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
                Log::error('Failed to fetch Cyclos product', [
                    'cyclos_user_id' => $cyclosUserId,
                    'product_id' => $productId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                if ($this->fallbackToMock) {
                    return $this->mockGetProduct($cyclosUserId, $productId);
                }

                return null;
            }

            $product = $response->json();

            // Format the product
            if (is_array($product)) {
                if (isset($product['unlimitedStock']) && $product['unlimitedStock'] === true) {
                    $product['quantity'] = 1000000;
                } elseif (isset($product['stockQuantity'])) {
                    $product['quantity'] = (float) $product['stockQuantity'];
                } else {
                    $product['quantity'] = 1000000;
                }

                if (!isset($product['id']) && isset($product['_id'])) {
                    $product['id'] = $product['_id'];
                }
            }

            return $product;
        } catch (Exception $e) {
            Log::error('Exception in getProduct', [
                'error' => $e->getMessage(),
            ]);

            if ($this->fallbackToMock) {
                return $this->mockGetProduct($cyclosUserId, $productId);
            }

            return null;
        }
    }

    /**
     * Upload an image to Cyclos and return the image key
     */
    public function uploadImage(string $cyclosUserId, UploadedFile $image): ?string
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockUploadImage($cyclosUserId, $image);
        }

        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/images";

            Log::info('Uploading image to Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'url' => $url,
                'file_name' => $image->getClientOriginalName(),
                'file_size' => $image->getSize(),
                'mime_type' => $image->getMimeType(),
            ]);

            $fileContents = file_get_contents($image->getRealPath());

            if ($fileContents === false) {
                Log::error('Failed to read image file contents');
                return null;
            }

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->attach(
                    'file',
                    $fileContents,
                    $image->getClientOriginalName(),
                    ['Content-Type' => $image->getMimeType()]
                )
                ->timeout(60)
                ->post($url);

            if ($response->failed()) {
                Log::error('Failed to upload image to Cyclos', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                if ($this->fallbackToMock) {
                    return $this->mockUploadImage($cyclosUserId, $image);
                }

                return null;
            }

            $result = $response->json();
            $imageKey = $result['key'] ?? null;

            Log::info('Image uploaded successfully', ['image_key' => $imageKey]);

            return $imageKey;
        } catch (Exception $e) {
            Log::error('Exception in uploadImage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($this->fallbackToMock) {
                return $this->mockUploadImage($cyclosUserId, $image);
            }

            return null;
        }
    }

    /**
     * Delete an image from Cyclos
     */
    public function deleteImage(string $cyclosUserId, string $imageKey): bool
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockDeleteImage($cyclosUserId, $imageKey);
        }

        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/images/{$imageKey}";

            Log::info('Deleting image from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'image_key' => $imageKey,
                'url' => $url,
            ]);

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->delete($url);

            if ($response->failed()) {
                Log::error('Failed to delete image from Cyclos', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (Exception $e) {
            Log::error('Exception in deleteImage', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create a product in Cyclos marketplace
     */
    public function createProduct(string $cyclosUserId, array $productData, ?UploadedFile $image = null): array
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockCreateProduct($cyclosUserId, $productData, $image);
        }

        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/marketplace";

            // Get the category ID from the array (single category)
            $categoryId = null;
            if (isset($productData['categories']) && is_array($productData['categories']) && !empty($productData['categories'])) {
                $categoryId = $productData['categories'][0];

                // Get the internal name for this category
                $categories = $this->getMarketplaceCategories();
                $internalName = $this->findInternalNameById($categories, $categoryId);

                Log::info('Found internal name for category', [
                    'category_id' => $categoryId,
                    'internal_name' => $internalName
                ]);

                if (!$internalName) {
                    return [
                        'success' => false,
                        'message' => 'Could not find internal name for selected category',
                        'needs_category' => true
                    ];
                }

                // Use internal name for the category
                $categoryId = $internalName;
            }

            // If no category provided, return error
            if (empty($categoryId)) {
                Log::error('No category ID provided or found');
                return [
                    'success' => false,
                    'message' => 'A category is required. Please select a category.',
                    'needs_category' => true
                ];
            }

            // Determine publication period based on status
            $isActive = isset($productData['status']) ? (bool) $productData['status'] : true;

            $beginDate = now();
            $endDate = null;

            if ($isActive) {
                $endDate = now()->addYear();
            } else {
                $beginDate = now()->subDays(1);
                $endDate = now()->subDays(1);
            }

            // Prepare custom values array
            $customValues = [
                'tradeType' => $productData['trade_type'] ?? 'tradeTypeOffer',
            ];

            // Only add barcode if it's provided and we have the custom field configured
            // Check if barcode custom field exists in marketplace data
            $customFields = $this->getMarketplaceCustomFields();
            $hasBarcodeField = $this->hasCustomField($customFields, 'barcode');

            if (!empty($productData['barcode']) && $hasBarcodeField) {
                $customValues['barcode'] = $productData['barcode'];
                Log::info('Adding barcode to product', ['barcode' => $productData['barcode']]);
            } elseif (!empty($productData['barcode']) && !$hasBarcodeField) {
                Log::warning('Barcode field not available in marketplace, skipping', [
                    'barcode' => $productData['barcode']
                ]);
            }

            // Prepare the product data for Cyclos API
            $cyclosProductData = [
                'name' => $productData['name'],
                'description' => $productData['description'] ?? '',
                'publicationPeriod' => [
                    'begin' => $beginDate->toIso8601String(),
                    'end' => $endDate ? $endDate->toIso8601String() : null,
                ],
                'categories' => [$categoryId],
                'currency' => 'unit',
                'price' => (float) $productData['price'],
                'unlimitedStock' => isset($productData['unlimited_stock']) ? (bool) $productData['unlimited_stock'] : false,
                'stockQuantity' => (int) ($productData['quantity'] ?? 1),
                'allowDecimalQuantity' => false,
                'kind' => 'webshop',
                'customValues' => $customValues,
            ];

            // Add barcode as a separate field if it's a standard field (not custom)
            // Some Cyclos versions have barcode as a separate field
            if (!empty($productData['barcode']) && !$hasBarcodeField) {
                // Try to add barcode as a separate field if supported
                $cyclosProductData['barcode'] = $productData['barcode'];
            }

            // Remove end date if null
            if ($cyclosProductData['publicationPeriod']['end'] === null) {
                unset($cyclosProductData['publicationPeriod']['end']);
            }

            Log::info('Creating product in Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'url' => $url,
                'category_internal_name' => $categoryId,
                'is_active' => $isActive,
                'has_barcode' => !empty($productData['barcode']),
                'has_barcode_field' => $hasBarcodeField,
                'product_data' => $cyclosProductData,
            ]);

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->post($url, $cyclosProductData);

            if ($response->failed()) {
                $errorData = $response->json();

                $errorMessage = 'Failed to create product';
                $needsCategory = false;

                if (isset($errorData['propertyErrors']['categories'])) {
                    $errorMessage = 'Category error: ' . implode(', ', $errorData['propertyErrors']['categories']);
                    $needsCategory = true;
                } elseif (isset($errorData['entityType']) && $errorData['entityType'] === 'AdCustomField') {
                    // Handle custom field errors
                    $errorMessage = 'Custom field error: ' . ($errorData['key'] ?? 'unknown field') . ' is not configured in the marketplace.';
                    Log::warning('Custom field error in product creation', [
                        'field' => $errorData['key'] ?? 'unknown',
                        'error' => $errorData
                    ]);
                } elseif (isset($errorData['message'])) {
                    $errorMessage = $errorData['message'];
                } elseif (isset($errorData['code'])) {
                    $errorMessage = $errorData['code'] . ': ' . json_encode($errorData);
                }

                Log::error('Failed to create product in Cyclos', [
                    'status' => $response->status(),
                    'error' => $errorData,
                    'category_internal_name' => $categoryId,
                    'response_body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => $errorData,
                    'needs_category' => $needsCategory
                ];
            }

            $data = $response->json();

            // Check if the response contains a valid product ID
            $productId = null;
            if (isset($data['id'])) {
                $productId = $data['id'];
            } elseif (isset($data['_id'])) {
                $productId = $data['_id'];
            } elseif (is_string($data) && strlen($data) > 10) {
                $productId = $data;
            } else {
                $productId = $data;
            }

            if (!$productId) {
                Log::error('No product ID returned from Cyclos', ['response' => $data]);
                return [
                    'success' => false,
                    'message' => 'Product created but no ID returned',
                ];
            }

            Log::info('Product created successfully in Cyclos', [
                'product_id' => $productId,
                'category_internal_name' => $categoryId,
                'full_response' => $data
            ]);

            $imageUploaded = false;
            if ($image) {
                $imageUploaded = $this->assignImageToProduct($productId, $image);

                if (!$imageUploaded) {
                    Log::warning('Product created but image upload failed', [
                        'product_id' => $productId,
                    ]);
                    // Continue - product was created successfully even if image upload fails
                }
            }

            return [
                'success' => true,
                'product_id' => $productId,
                'image_uploaded' => $imageUploaded,
                'data' => $data,
            ];
        } catch (Exception $e) {
            Log::error('Exception in createProduct', [
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
     * Assign an image to an existing product using the marketplace images endpoint
     * POST /api/marketplace/{productId}/images
     */
    public function assignImageToProduct(string $productId, UploadedFile $image): bool
    {
        try {
            $url = $this->baseUrl . "/api/marketplace/{$productId}/images";

            Log::info('Assigning image to product', [
                'product_id' => $productId,
                'url' => $url,
                'file_name' => $image->getClientOriginalName(),
                'file_size' => $image->getSize(),
                'mime_type' => $image->getMimeType(),
            ]);

            $fileContents = file_get_contents($image->getRealPath());

            if ($fileContents === false) {
                Log::error('Failed to read image file contents');
                return false;
            }

            /** @var Illuminate\Http\Client\Response $response */
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'text/plain',
                    'Channel' => $this->channel,
                ])
                ->attach(
                    'image',
                    $fileContents,
                    $image->getClientOriginalName(),
                    ['Content-Type' => $image->getMimeType()]
                )
                ->timeout(60)
                ->post($url);

            if ($response->failed()) {
                Log::error('Failed to assign image to product', [
                    'product_id' => $productId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            Log::info('Image assigned to product successfully', [
                'product_id' => $productId,
                'response' => $response->body(),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Exception in assignImageToProduct', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'product_id' => $productId,
            ]);
            return false;
        }
    }

    protected function getMarketplaceCustomFields(): array
    {
        $data = $this->getMarketplaceData();
        return $data['customFields'] ?? [];
    }

    protected function hasCustomField(array $customFields, string $fieldName): bool
    {
        foreach ($customFields as $field) {
            if (($field['internalName'] ?? '') === $fieldName) {
                return true;
            }
        }
        return false;
    }
    /**
     * Update a product in Cyclos marketplace
     */
    public function updateProduct(string $cyclosUserId, string $productId, array $productData, ?UploadedFile $image = null, ?string $existingImageKey = null): array
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockUpdateProduct($cyclosUserId, $productId, $productData, $image, $existingImageKey);
        }

        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/marketplace/{$productId}";

            // Handle image upload if new image is provided
            $imageKey = $existingImageKey;
            if ($image) {
                if ($existingImageKey) {
                    $this->deleteImage($cyclosUserId, $existingImageKey);
                }

                $newImageKey = $this->uploadImage($cyclosUserId, $image);
                if ($newImageKey) {
                    $imageKey = $newImageKey;
                }
            }

            // Handle category
            $categoryId = null;
            if (isset($productData['categories']) && is_array($productData['categories']) && !empty($productData['categories'])) {
                $categoryId = $productData['categories'][0];
                $internalName = $this->getCategoryInternalName($categoryId);
                if ($internalName) {
                    $categoryId = $internalName;
                }
            }

            // Determine publication period based on status
            $isActive = isset($productData['status']) ? (bool) $productData['status'] : true;

            $beginDate = now();
            $endDate = null;

            if ($isActive) {
                $endDate = now()->addYear();
            } else {
                $beginDate = now()->subDays(1);
                $endDate = now()->subDays(1);
            }

            // Prepare the product data for Cyclos API
            $cyclosProductData = [
                'name' => $productData['name'],
                'description' => $productData['description'] ?? '',
                'publicationPeriod' => [
                    'begin' => $beginDate->toIso8601String(),
                    'end' => $endDate ? $endDate->toIso8601String() : null,
                ],
                'currency' => 'unit',
                'price' => (float) $productData['price'],
                'unlimitedStock' => isset($productData['unlimited_stock']) ? (bool) $productData['unlimited_stock'] : false,
                'stockQuantity' => (int) ($productData['quantity'] ?? 1),
                'allowDecimalQuantity' => false,
                'kind' => 'webshop',
                'customValues' => [
                    'tradeType' => $productData['trade_type'] ?? 'tradeTypeOffer',
                ],
            ];

            // Remove end date if null
            if ($cyclosProductData['publicationPeriod']['end'] === null) {
                unset($cyclosProductData['publicationPeriod']['end']);
            }

            // Add category only if provided
            if (!empty($categoryId)) {
                $cyclosProductData['categories'] = [$categoryId];
            }

            // Add barcode if provided
            if (!empty($productData['barcode'])) {
                $cyclosProductData['customValues']['barcode'] = $productData['barcode'];
            }

            // Add images if available
            if ($imageKey) {
                $cyclosProductData['images'] = [$imageKey];
            }

            Log::info('Updating product in Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'product_id' => $productId,
                'url' => $url,
                'is_active' => $isActive,
                'product_data' => $cyclosProductData,
            ]);

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->put($url, $cyclosProductData);

            /** @var \Illuminate\Http\Client\Response $response */
            if ($response->failed()) {
                $errorData = $response->json();

                Log::error('Failed to update product in Cyclos', [
                    'status' => $response->status(),
                    'error' => $errorData,
                    'response_body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to update product: ' . json_encode($errorData),
                    'errors' => $errorData,
                ];
            }

            $data = $response->json();

            Log::info('Product updated successfully in Cyclos', [
                'product_id' => $productId,
            ]);

            return [
                'success' => true,
                'product_id' => $productId,
                'data' => $data,
            ];
        } catch (Exception $e) {
            Log::error('Exception in updateProduct', [
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
     * Delete a product from Cyclos marketplace
     */
    public function deleteProduct(string $cyclosUserId, string $productId): array
    {
        // Check if mock mode is enabled
        if ($this->mockMode) {
            return $this->mockDeleteProduct($cyclosUserId, $productId);
        }

        try {
            $url = $this->baseUrl . "/api/marketplace/{$productId}";

            Log::info('Deleting product from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'product_id' => $productId,
                'url' => $url,
            ]);

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->delete($url);

            if ($response->failed()) {
                Log::error('Failed to delete product from Cyclos', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to delete product',
                ];
            }

            Log::info('Product deleted successfully from Cyclos', [
                'product_id' => $productId,
            ]);

            return [
                'success' => true,
                'message' => 'Product deleted successfully',
            ];
        } catch (Exception $e) {
            Log::error('Exception in deleteProduct', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
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

        if (!empty($filters['min_price'])) {
            $params['minPrice'] = (float) $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $params['maxPrice'] = (float) $filters['max_price'];
        }

        if (!empty($filters['kind'])) {
            $params['kind'] = $filters['kind'];
        }

        $params['pageSize'] = $filters['per_page'] ?? 20;
        $params['page'] = $filters['page'] ?? 0;

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
        }
        elseif (isset($rawProduct['images']) && is_array($rawProduct['images']) && !empty($rawProduct['images'])) {
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
        $createdAt = $rawProduct['creationDate'] ?? $rawProduct['createdAt'] ?? null;
        $updatedAt = $rawProduct['lastModifiedDate'] ?? $rawProduct['updatedAt'] ?? null;

        return [
            'id' => $rawProduct['id'] ?? $rawProduct['_id'] ?? null,
            'name' => $rawProduct['name'] ?? 'Unnamed Product',
            'description' => $rawProduct['description'] ?? '',
            'barcode' => $barcode,
            'price' => $price,
            'quantity' => $quantity,
            'unlimited_stock' => (bool) ($rawProduct['unlimitedStock'] ?? false),
            'status' => $this->determineProductStatus($rawProduct),
            'image_url' => $imageUrl,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'raw' => $rawProduct,
        ];
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
    protected function mockGetMarketplaceData(): array
    {
        Log::info('Mock: Getting marketplace data');

        return [
            'categories' => [
                [
                    'id' => 'cat-1',
                    'internalName' => 'category_electronics',
                    'name' => 'Electronics',
                    'children' => [
                        [
                            'id' => 'cat-1-1',
                            'internalName' => 'category_phones',
                            'name' => 'Phones',
                            'children' => []
                        ]
                    ]
                ],
                [
                    'id' => 'cat-2',
                    'internalName' => 'category_clothing',
                    'name' => 'Clothing',
                    'children' => []
                ]
            ],
            'currencies' => [
                ['id' => 'unit', 'symbol' => $this->currencySymbol, 'name' => $this->currencyName]
            ],
            'customFields' => [
                [
                    'internalName' => 'tradeType',
                    'possibleValues' => [
                        ['internalName' => 'tradeTypeOffer', 'value' => 'Offer'],
                        ['internalName' => 'tradeTypeDemand', 'value' => 'Demand']
                    ]
                ]
            ]
        ];
    }

    protected function mockGetUserProducts(string $cyclosUserId, array $params = []): array
    {
        Log::info('Mock: Getting user products', [
            'cyclos_user_id' => $cyclosUserId,
            'params' => $params
        ]);

        $mockProducts = [
            [
                'id' => 'prod-1',
                'name' => 'Sample Product 1',
                'description' => 'This is a sample product for testing',
                'price' => 100.00,
                'stockQuantity' => 50,
                'unlimitedStock' => false,
                'currency' => 'unit',
                'publicationPeriod' => [
                    'begin' => now()->subDays(10)->toISOString(),
                    'end' => now()->addMonths(6)->toISOString()
                ],
                'images' => [],
                'customValues' => [
                    'tradeType' => 'tradeTypeOffer',
                    'barcode' => '123456789'
                ],
                'creationDate' => now()->subDays(5)->toISOString()
            ],
            [
                'id' => 'prod-2',
                'name' => 'Sample Product 2',
                'description' => 'Another sample product',
                'price' => 50.00,
                'stockQuantity' => 0,
                'unlimitedStock' => false,
                'currency' => 'unit',
                'publicationPeriod' => [
                    'begin' => now()->subDays(20)->toISOString(),
                    'end' => now()->subDays(1)->toISOString()
                ],
                'images' => [],
                'customValues' => [
                    'tradeType' => 'tradeTypeDemand',
                    'barcode' => '987654321'
                ],
                'creationDate' => now()->subDays(15)->toISOString()
            ]
        ];

        $products = array_map(function ($product) {
            if (isset($product['unlimitedStock']) && $product['unlimitedStock'] === true) {
                $product['quantity'] = 1000000;
            } elseif (isset($product['stockQuantity'])) {
                $product['quantity'] = (float) $product['stockQuantity'];
            } else {
                $product['quantity'] = 1000000;
            }
            return $product;
        }, $mockProducts);

        return [
            'products' => $products,
            'headers' => [
                'x-current-page' => 0,
                'x-page-size' => 20,
                'x-total-count' => count($products),
                'x-page-count' => 1,
                'x-has-next-page' => false,
            ]
        ];
    }

    protected function mockGetProduct(string $cyclosUserId, string $productId): ?array
    {
        Log::info('Mock: Getting product', [
            'cyclos_user_id' => $cyclosUserId,
            'product_id' => $productId
        ]);

        return [
            'id' => $productId,
            'name' => 'Mock Product',
            'description' => 'This is a mock product for testing',
            'price' => 75.00,
            'stockQuantity' => 100,
            'unlimitedStock' => false,
            'currency' => 'unit',
            'publicationPeriod' => [
                'begin' => now()->subDays(5)->toISOString(),
                'end' => now()->addMonths(3)->toISOString()
            ],
            'images' => [],
            'customValues' => [
                'tradeType' => 'tradeTypeOffer',
                'barcode' => 'MOCK123'
            ],
            'creationDate' => now()->subDays(3)->toISOString(),
            'quantity' => 100
        ];
    }

    protected function mockUploadImage(string $cyclosUserId, UploadedFile $image): ?string
    {
        Log::info('Mock: Uploading image', [
            'cyclos_user_id' => $cyclosUserId,
            'file_name' => $image->getClientOriginalName()
        ]);

        return 'mock-image-key-' . uniqid();
    }

    protected function mockDeleteImage(string $cyclosUserId, string $imageKey): bool
    {
        Log::info('Mock: Deleting image', [
            'cyclos_user_id' => $cyclosUserId,
            'image_key' => $imageKey
        ]);

        return true;
    }

    protected function mockCreateProduct(string $cyclosUserId, array $productData, ?UploadedFile $image = null): array
    {
        Log::info('Mock: Creating product', [
            'cyclos_user_id' => $cyclosUserId,
            'product_data' => $productData,
            'has_image' => $image !== null
        ]);

        return [
            'success' => true,
            'product_id' => 'mock-prod-' . uniqid(),
            'data' => [
                'id' => 'mock-prod-' . uniqid(),
                'name' => $productData['name']
            ]
        ];
    }

    protected function mockUpdateProduct(string $cyclosUserId, string $productId, array $productData, ?UploadedFile $image = null, ?string $existingImageKey = null): array
    {
        Log::info('Mock: Updating product', [
            'cyclos_user_id' => $cyclosUserId,
            'product_id' => $productId,
            'product_data' => $productData
        ]);

        return [
            'success' => true,
            'product_id' => $productId,
            'data' => [
                'id' => $productId,
                'name' => $productData['name']
            ]
        ];
    }

    protected function mockDeleteProduct(string $cyclosUserId, string $productId): array
    {
        Log::info('Mock: Deleting product', [
            'cyclos_user_id' => $cyclosUserId,
            'product_id' => $productId
        ]);

        return [
            'success' => true,
            'message' => 'Product deleted successfully'
        ];
    }
}
