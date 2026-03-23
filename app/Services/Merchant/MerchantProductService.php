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
    protected ?array $marketplaceData = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.cyclos.base_url'), '/');
        $this->username = (string) config('services.cyclos.username');
        $this->password = (string) config('services.cyclos.password');
        $this->channel = (string) config('services.cyclos.channel', 'webServices');

        $this->currencyCode = (string) config('services.cyclos.currency_code', 'FT');
        $this->currencySymbol = (string) config('services.cyclos.currency_symbol', 'FT');
        $this->currencyName = (string) config('services.cyclos.currency_name', 'FavorTokens');
        $this->adminUserId = (string) config('services.cyclos.admin_user_id');

        $this->validateRequiredConfig();
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

        if (empty($this->adminUserId)) {
            $missing[] = 'services.cyclos.admin_user_id';
        }

        if (!empty($missing)) {
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
     * Get all marketplace data from Cyclos
     */
    public function getMarketplaceData(): array
    {
        if ($this->marketplaceData !== null) {
            return $this->marketplaceData;
        }

        try {
            $url = $this->baseUrl . "/api/marketplace/data-for-search?kind=webshop";

            Log::info('Fetching marketplace data from Cyclos', [
                'url' => $url,
            ]);

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
                return [];
            }

            $data = $response->json();

            Log::info('Marketplace data fetched successfully', [
                'categories_count' => count($data['categories'] ?? []),
                'currencies_count' => count($data['currencies'] ?? [])
            ]);

            // Cache the data for 1 hour
            Cache::put('cyclos_marketplace_data', $data, 3600);

            $this->marketplaceData = $data;
            return $data;
        } catch (Exception $e) {
            Log::error('Exception in getMarketplaceData', [
                'error' => $e->getMessage(),
            ]);
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
                Log::debug('Found category', [
                    'id' => $id,
                    'name' => $name,
                    'id_type' => gettype($id),
                    'id_length' => strlen($id)
                ]);

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
        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/marketplace";

            Log::info('Fetching user products from Cyclos', [
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
                Log::error('Failed to fetch Cyclos products', [
                    'cyclos_user_id' => $cyclosUserId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['products' => [], 'headers' => []];
            }

            $products = $response->json();

            $headers = [
                'x-current-page' => $response->header('X-Current-Page'),
                'x-page-size' => $response->header('X-Page-Size'),
                'x-total-count' => $response->header('X-Total-Count'),
                'x-page-count' => $response->header('X-Page-Count'),
                'x-has-next-page' => $response->header('X-Has-Next-Page'),
            ];

            Log::info('Cyclos API headers received', $headers);

            // Helper function to add quantity field
            $addQuantityField = function ($product) {
                if (isset($product['unlimitedStock']) && $product['unlimitedStock'] === true) {
                    $product['quantity'] = 1000000; // 1 million for unlimited stock
                } elseif (isset($product['stockQuantity'])) {
                    $product['quantity'] = (float) $product['stockQuantity'];
                } else {
                    $product['quantity'] = 1000000; // Default to unlimited
                }
                return $product;
            };

            // Handle different response formats and return both products and headers
            if (is_array($products)) {
                // Check if it's a paginated response with 'items' or 'data' key
                if (isset($products['items']) && is_array($products['items'])) {
                    $products['items'] = array_map($addQuantityField, $products['items']);
                    return ['products' => $products['items'], 'headers' => $headers];
                }
                if (isset($products['data']) && is_array($products['data'])) {
                    $products['data'] = array_map($addQuantityField, $products['data']);
                    return ['products' => $products['data'], 'headers' => $headers];
                }
                // If it's a simple array of products
                if (array_keys($products) === range(0, count($products) - 1)) {
                    return ['products' => array_map($addQuantityField, $products), 'headers' => $headers];
                }
                // If it's a single product
                if (isset($products['id'])) {
                    return ['products' => [$addQuantityField($products)], 'headers' => $headers];
                }
            }

            return ['products' => is_array($products) ? array_map($addQuantityField, $products) : [], 'headers' => $headers];
        } catch (Exception $e) {
            Log::error('Exception in getUserProducts', [
                'error' => $e->getMessage(),
            ]);
            return ['products' => [], 'headers' => []];
        }
    }

    /**
     * Get a single product by ID
     */
    public function getProduct(string $cyclosUserId, string $productId): ?array
    {
        try {
            $url = $this->baseUrl . "/api/marketplace/{$productId}";

            Log::info('Fetching single product from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'product_id' => $productId,
                'url' => $url,
            ]);

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
                return null;
            }

            $product = $response->json();

            // Add quantity field based on stock information
            if (is_array($product)) {
                if (isset($product['unlimitedStock']) && $product['unlimitedStock'] === true) {
                    $product['quantity'] = 1000000; // 1 million for unlimited stock
                } elseif (isset($product['stockQuantity'])) {
                    $product['quantity'] = (float) $product['stockQuantity'];
                } else {
                    $product['quantity'] = 1000000; // Default to unlimited
                }
            }

            return $product;
        } catch (Exception $e) {
            Log::error('Exception in getProduct', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Upload an image to Cyclos and return the image key
     */
    public function uploadImage(string $cyclosUserId, UploadedFile $image): ?string
    {
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
            return null;
        }
    }

    /**
     * Delete an image from Cyclos
     */
    public function deleteImage(string $cyclosUserId, string $imageKey): bool
    {
        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/images/{$imageKey}";

            Log::info('Deleting image from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'image_key' => $imageKey,
                'url' => $url,
            ]);

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
    /**
     * Create a product in Cyclos marketplace
     */
    public function createProduct(string $cyclosUserId, array $productData, ?UploadedFile $image = null): array
    {
        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/marketplace";

            // Upload image if provided
            $imageKey = null;
            if ($image) {
                $imageKey = $this->uploadImage($cyclosUserId, $image);
            }

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
                'kind' => 'simple',
                'customValues' => [
                    'tradeType' => $productData['trade_type'] ?? 'tradeTypeOffer',
                ],
            ];

            // Add barcode if provided
            if (!empty($productData['barcode'])) {
                $cyclosProductData['customValues']['barcode'] = $productData['barcode'];
            }

            // Add images if uploaded
            if ($imageKey) {
                $cyclosProductData['images'] = [$imageKey];
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
                'product_data' => $cyclosProductData,
            ]);

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
                // Some APIs return just the ID as a string
                $productId = $data;
            }

            Log::info('Product created successfully in Cyclos', [
                'product_id' => $productId,
                'category_internal_name' => $categoryId,
                'full_response' => $data
            ]);

            return [
                'success' => true,
                'product_id' => $productId,
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
     * Update a product in Cyclos marketplace
     */
    public function updateProduct(string $cyclosUserId, string $productId, array $productData, ?UploadedFile $image = null, ?string $existingImageKey = null): array
    {
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
                'kind' => 'simple',
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
        try {
            $url = $this->baseUrl . "/api/{$cyclosUserId}/marketplace/{$productId}";

            Log::info('Deleting product from Cyclos', [
                'cyclos_user_id' => $cyclosUserId,
                'product_id' => $productId,
                'url' => $url,
            ]);

            $product = $this->getProduct($cyclosUserId, $productId);
            if ($product && isset($product['images']) && is_array($product['images'])) {
                foreach ($product['images'] as $image) {
                    $this->deleteImage($cyclosUserId, $image);
                }
            }

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
        $params['pageNumber'] = $filters['page'] ?? 0;

        return $this->getUserProducts($cyclosUserId, $params);
    }

    /**
     * Format Cyclos product to match the expected format for views
     */
    public function formatProduct(array $rawProduct): array
    {
        $imageKey = $rawProduct['images'][0] ?? null;
        $imageUrl = $imageKey ? $this->baseUrl . "/api/files/{$imageKey}/data" : null;

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
            'image_key' => $imageKey,
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
                Log::debug('Product inactive - begin date in future', [
                    'now' => $now->toDateTimeString(),
                    'begin' => $begin->toDateTimeString()
                ]);
                return false;
            }

            // If end date is in the past, product is expired
            if ($end && $now > $end) {
                Log::debug('Product inactive - end date in past', [
                    'now' => $now->toDateTimeString(),
                    'end' => $end->toDateTimeString()
                ]);
                return false;
            }
        }

        // Check stock
        $unlimitedStock = (bool) ($product['unlimitedStock'] ?? false);
        $stockQuantity = (int) ($product['stockQuantity'] ?? 0);

        if (!$unlimitedStock && $stockQuantity <= 0) {
            Log::debug('Product inactive - out of stock', [
                'unlimited' => $unlimitedStock,
                'quantity' => $stockQuantity
            ]);
            return false;
        }

        Log::debug('Product active');
        return true; // Active
    }
}
