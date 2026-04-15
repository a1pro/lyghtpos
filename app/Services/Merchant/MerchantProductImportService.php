<?php
// app/Services/Merchant/MerchantProductImportService.php

namespace App\Services\Merchant;

use App\Models\MerchantProductImport;
use App\Models\ImportFailureLog;
use App\Jobs\ProcessProductImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class MerchantProductImportService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $channel;
    protected string $currencyCode;
    protected string $currencySymbol;
    protected string $currencyName;
    protected string $adminUserId;
    protected string $defaultCategoryId;
    protected int $delayBetweenProducts = 2;
    protected int $maxProductsPerImport = 5000;

    public function __construct()
    {
        $this->loadSettings();
        $this->validateRequiredConfig();

        Log::info('MerchantProductService initialized', [
            'currency_code' => $this->currencyCode,
            'currency_symbol' => $this->currencySymbol,
            'admin_user_id' => $this->adminUserId,
            'default_category_id' => $this->defaultCategoryId,
            'environment' => app()->environment(),
        ]);
    }

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

            $this->baseUrl = rtrim($getStringValue('services.cyclos.base_url', ''), '/');
            $this->username = $getStringValue('services.cyclos.username', '');
            $this->password = $getStringValue('services.cyclos.password', '');
            $this->channel = $getStringValue('services.cyclos.channel', 'webServices');
            $this->currencyCode = $getStringValue('services.cyclos.currency_code', 'TT');
            $this->currencySymbol = $getStringValue('services.cyclos.currency_symbol', 'TT');
            $this->currencyName = $getStringValue('services.cyclos.currency_name', 'TradeTokens');
            $this->adminUserId = $getStringValue('services.cyclos.admin_user_id', '');
            $this->defaultCategoryId = $getStringValue('services.cyclos.default_category_id', '7157739016492146860');

            Log::info('MerchantProductService settings loaded from config', [
                'default_category_id' => $this->defaultCategoryId,
                'base_url' => $this->baseUrl,
                'username' => $this->username,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to load MerchantProductService settings', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to load MerchantProductService configuration: ' . $e->getMessage());
        }
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
        if (empty($this->defaultCategoryId)) {
            $missing[] = 'services.cyclos.default_category_id';
        }

        if (!empty($missing)) {
            Log::error('Missing required Cyclos configuration', ['missing' => $missing]);
            throw new \RuntimeException('Missing required Cyclos configuration: ' . implode(', ', $missing));
        }
    }

    public function processImport($file, $merchant): array
    {
        $cyclosUsername = $merchant->cyclos_username;
        
        if (empty($cyclosUsername)) {
            Log::error('User does not have a Cyclos username', [
                'user_id' => $merchant->id,
                'user_email' => $merchant->email,
            ]);
            throw new \Exception('Your account is not properly configured for product imports. Please contact support.');
        }
        
        $csvData = $this->parseCsvFile($file, $cyclosUsername);
        
        if (!$csvData['valid']) {
            throw new \Exception($csvData['error']);
        }
        
        $cyclosResult = $this->uploadToCyclos($file, $merchant);
        $cyclosImportId = $cyclosResult['id'];
        
        $import = MerchantProductImport::create([
            'user_id' => $merchant->id,
            'cyclos_import_id' => $cyclosImportId,
            'file_name' => $file->getClientOriginalName(),
            'total_rows' => $csvData['total_rows'],
            'processed_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
            'status' => MerchantProductImport::STATUS_PENDING,
            'error_log' => []
        ]);
        
        Cache::put("import:{$import->id}:processed", 0, now()->addDays(7));
        Cache::put("import:{$import->id}:success", 0, now()->addDays(7));
        Cache::put("import:{$import->id}:failed", 0, now()->addDays(7));
        
        $delaySeconds = 0;
        
        foreach ($csvData['rows'] as $index => $row) {
            ProcessProductImport::dispatch(
                $import,
                $row,
                $index + 1,
                $csvData['total_rows'],
                $cyclosUsername
            )->onQueue('product-imports')
             ->delay(now()->addSeconds($delaySeconds));
            
            $delaySeconds += $this->delayBetweenProducts;
        }
        
        $import->status = MerchantProductImport::STATUS_PROCESSING;
        $import->started_at = now();
        $import->save();
        
        $estimatedMinutes = round(($csvData['total_rows'] * $this->delayBetweenProducts) / 60, 1);
        
        Log::info('Import dispatched', [
            'import_id' => $import->id,
            'cyclos_import_id' => $cyclosImportId,
            'merchant_id' => $merchant->id,
            'total_products' => $csvData['total_rows'],
            'estimated_minutes' => $estimatedMinutes
        ]);
        
        return [
            'id' => $cyclosImportId,
            'cyclos_import_id' => $cyclosImportId,
            'import_id' => $import->id,
            'total_products' => $csvData['total_rows'],
            'estimated_time_minutes' => $estimatedMinutes,
            'estimated_time_formatted' => $this->formatDuration($estimatedMinutes * 60)
        ];
    }
    
    protected function parseCsvFile($file, $cyclosUsername): array
    {
        $inputPath = $file->getRealPath();
        $input = fopen($inputPath, 'r');
        
        if (!$input) {
            return ['valid' => false, 'error' => 'Cannot read file'];
        }
        
        $header = fgetcsv($input);
        if (!$header || empty($header)) {
            fclose($input);
            return ['valid' => false, 'error' => 'CSV file is empty or invalid'];
        }
        
        $header = array_map('trim', $header);
        $header = array_map('strtolower', $header);
        
        $required = ['name', 'price'];
        $missing = array_diff($required, $header);
        
        if (!empty($missing)) {
            fclose($input);
            return ['valid' => false, 'error' => 'Missing required columns: ' . implode(', ', $missing)];
        }
        
        $rows = [];
        $rowCount = 0;
        
        while (($row = fgetcsv($input)) !== false && $rowCount < $this->maxProductsPerImport) {
            $rowData = [];
            foreach ($header as $idx => $col) {
                $value = isset($row[$idx]) ? trim($row[$idx]) : '';
                $rowData[$col] = $value;
            }
            
            $rowData['price'] = isset($rowData['price']) ? (float) $rowData['price'] : 0;
            $rowData['stockquantity'] = isset($rowData['stockquantity']) ? (int) $rowData['stockquantity'] : 0;
            $rowData['user'] = $cyclosUsername;
            $rowData['field.tradetype'] = 'tradeTypeDemand';
            
            $rows[] = $rowData;
            $rowCount++;
        }
        
        fclose($input);
        
        if ($rowCount === 0) {
            return ['valid' => false, 'error' => 'No data rows found in CSV'];
        }
        
        return [
            'valid' => true,
            'total_rows' => $rowCount,
            'rows' => $rows,
            'header' => $header
        ];
    }
    
    public function upload($file)
    {
        $user = Auth::user();
        $cyclosUsername = $user->cyclos_username;
        
        if (empty($cyclosUsername)) {
            throw new \Exception('Your account is not properly configured for product imports. Please contact support.');
        }
        
        return $this->uploadToCyclos($file, $user);
    }
    
    protected function uploadToCyclos($file, $merchant): array
    {
        $url = $this->baseUrl . '/api/imported-files/ads';
        $cyclosUsername = $merchant->cyclos_username;
        
        Log::info('Uploading file to Cyclos', [
            'url' => $url,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ]);
        
        $processedFilePath = $this->prepareCsv($file, $cyclosUsername);
        
        $response = Http::withBasicAuth($this->username, $this->password)
            ->withHeaders([
                'Accept' => 'application/json',
                'Channel' => $this->channel,
            ])->attach(
                'file',
                file_get_contents($processedFilePath),
                basename($processedFilePath)
            )->post($url, [
                'params' => json_encode([
                    'currency' => 'unit',
                    'processAutomatically' => true
                ])
            ]);
        
        @unlink($processedFilePath);
        
        Log::info('Upload response from Cyclos', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);
        
        if (!$response->successful()) {
            Log::error('Failed to upload file to Cyclos', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Upload failed: ' . $response->body());
        }
        
        return [
            'id' => trim($response->body())
        ];
    }
    
    public function getImportDetails($id)
    {
        $url = $this->baseUrl . "/api/imported-file/{$id}";
        
        $response = Http::withBasicAuth($this->username, $this->password)
            ->withHeaders([
                'Accept' => 'application/json',
                'Channel' => $this->channel,
            ])->get($url);
        
        Log::info('Get import details response from Cyclos', [
            'status' => $response->status(),
            'import_id' => $id
        ]);
        
        if (!$response->successful()) {
            Log::error('Failed to fetch import details from Cyclos', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Failed to fetch import details');
        }
        
        return $response->json();
    }
    
    /**
     * Create a single product in Cyclos via API
     * Fixed: Category ID as string to prevent scientific notation
     */
    public function createProduct($cyclosUsername, array $productData): array
    {
        if (empty($cyclosUsername)) {
            return ['success' => false, 'error' => 'Cyclos username is required'];
        }
        
        // Get category ID - ensure it's treated as a string
        $categoryId = $this->defaultCategoryId;
        
        // Check if category_id is provided in CSV (exact match to header)
        if (!empty($productData['category_id'])) {
            // Convert to string to prevent scientific notation
            $categoryId = (string) $productData['category_id'];
        }
        
        // Clean the category ID - remove any quotes or extra spaces
        $categoryId = trim($categoryId, '"\'');
        
        // Build URL with the username
        $url = $this->baseUrl . '/api/' . urlencode($cyclosUsername) . '/marketplace';
        
        // Build payload with category as string array
        $payload = [
            'name' => (string) $productData['name'],
            'description' => (string) ($productData['description'] ?? $productData['name']),
            'publicationPeriod' => [
                'begin' => now()->toIso8601String(),
                'end' => now()->addYear()->toIso8601String()
            ],
            'categories' => [(string) $categoryId],  // Force string
            'customValues' => [
                'tradeType' => 'tradeTypeDemand'
            ],
            'currency' => 'unit',
            'price' => (float) $productData['price'],
            'unlimitedStock' => false,
            'stockQuantity' => (int) ($productData['stockquantity'] ?? 0),
            'allowDecimalQuantity' => false,
            'images' => [],
            'kind' => 'webshop'
        ];
        
        // Add product number if barcode provided
        if (!empty($productData['barcode'])) {
            $payload['productNumber'] = (string) $productData['barcode'];
        }
        
        // Log the request for debugging
        Log::info('Creating product via Cyclos API', [
            'url' => $url,
            'product' => $productData['name'],
            'category_id' => $categoryId,
            'category_id_type' => gettype($categoryId),
            'price' => $productData['price']
        ]);
        
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(60)
                ->post($url, $payload);
            
            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Product created successfully', [
                    'product_id' => $responseData['id'] ?? null,
                    'product_name' => $productData['name']
                ]);
                
                return [
                    'success' => true,
                    'product_id' => $responseData['id'] ?? null,
                    'data' => $responseData
                ];
            }
            
            $errorMessage = $this->parseErrorMessage($response);
            $statusCode = $response->status();
            
            Log::warning('Cyclos API error', [
                'status' => $statusCode,
                'error' => $errorMessage,
                'url' => $url,
                'product' => $productData['name'],
                'category_id_sent' => $categoryId
            ]);
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'status_code' => $statusCode
            ];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Connection error to Cyclos', [
                'error' => $e->getMessage(),
                'product' => $productData['name']
            ]);
            
            return [
                'success' => false,
                'error' => 'Network error: Could not connect to Cyclos API'
            ];
        } catch (\Exception $e) {
            Log::error('Product creation error', [
                'error' => $e->getMessage(),
                'product' => $productData['name']
            ]);
            
            return [
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    public function getCategories(): array
    {
        $url = $this->baseUrl . '/api/marketplace/data-for-search?kind=webshop';
        
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Channel' => $this->channel,
                ])
                ->timeout(30)
                ->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                $categories = [];
                
                if (isset($data['categories']) && is_array($data['categories'])) {
                    foreach ($data['categories'] as $category) {
                        $categories[] = [
                            'id' => (string) $category['id'],  // Force string
                            'name' => $category['name'],
                            'internalName' => $category['internalName'] ?? ''
                        ];
                        
                        if (isset($category['children']) && is_array($category['children'])) {
                            foreach ($category['children'] as $child) {
                                $categories[] = [
                                    'id' => (string) $child['id'],  // Force string
                                    'name' => $child['name'],
                                    'parent' => $category['name'],
                                    'internalName' => $child['internalName'] ?? ''
                                ];
                            }
                        }
                    }
                }
                
                Log::info('Categories fetched successfully', ['count' => count($categories)]);
                return $categories;
            }
            
            Log::warning('Failed to fetch categories', [
                'status' => $response->status()
            ]);
            
            return [];
            
        } catch (\Exception $e) {
            Log::error('Error fetching categories', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getCategoryIdByName(string $categoryName): ?string
    {
        $categories = $this->getCategories();
        
        foreach ($categories as $category) {
            if (strtolower($category['name']) === strtolower($categoryName)) {
                return $category['id'];
            }
        }
        
        return null;
    }

    public function getDefaultCategoryId(): string
    {
        return $this->defaultCategoryId;
    }
    
    private function prepareCsv($file, $cyclosUsername)
    {
        $inputPath = $file->getRealPath();
        $outputPath = storage_path('app/temp_import_' . time() . '.csv');
        
        $input = fopen($inputPath, 'r');
        $output = fopen($outputPath, 'w');
        
        $header = fgetcsv($input);
        $header = array_map('trim', $header);
        
        $hasUser = in_array('user', $header);
        $hasTradeType = in_array('field.tradeType', $header);
        
        if (!$hasUser) {
            $header[] = 'user';
        }
        if (!$hasTradeType) {
            $header[] = 'field.tradeType';
        }
        
        fputcsv($output, $header);
        
        while (($row = fgetcsv($input)) !== false) {
            if (!$hasUser) {
                $row[] = $cyclosUsername;
            }
            if (!$hasTradeType) {
                $row[] = 'tradeTypeDemand';
            }
            fputcsv($output, $row);
        }
        
        fclose($input);
        fclose($output);
        
        return $outputPath;
    }
    
    protected function parseErrorMessage($response): string
    {
        $errorBody = $response->body();
        $errorMessage = 'HTTP ' . $response->status() . ': ' . substr($errorBody, 0, 200);
        
        $errorJson = json_decode($errorBody, true);
        if ($errorJson) {
            if (isset($errorJson['exceptionMessage'])) {
                $errorMessage = $errorJson['exceptionMessage'];
            } elseif (isset($errorJson['message'])) {
                $errorMessage = $errorJson['message'];
            } elseif (isset($errorJson['error'])) {
                $errorMessage = $errorJson['error'];
            }
        }
        
        return $errorMessage;
    }
    
    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        } elseif ($seconds < 3600) {
            $minutes = round($seconds / 60, 1);
            return $minutes . ' minutes';
        } else {
            $hours = round($seconds / 3600, 1);
            return $hours . ' hours';
        }
    }
}