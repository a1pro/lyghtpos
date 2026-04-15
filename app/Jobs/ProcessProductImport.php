<?php
// app/Jobs/ProcessProductImport.php

namespace App\Jobs;

use App\Models\MerchantProductImport;
use App\Models\ImportFailureLog;
use App\Services\Merchant\MerchantProductImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 120;
    public $tries = 3;
    public $backoff = [30, 60, 120];
    
    protected $import;
    protected $productData;
    protected $rowNumber;
    protected $totalRows;
    protected $cyclosUsername;
    
    public function __construct(
        MerchantProductImport $import, 
        array $productData, 
        int $rowNumber, 
        int $totalRows, 
        string $cyclosUsername
    ) {
        $this->import = $import;
        $this->productData = $productData;
        $this->rowNumber = $rowNumber;
        $this->totalRows = $totalRows;
        $this->cyclosUsername = $cyclosUsername;
    }
    
    public function handle(MerchantProductImportService $service)
    {
        // Check if import was cancelled
        if ($this->import->status === MerchantProductImport::STATUS_FAILED) {
            Log::info("Import cancelled, skipping job", [
                'import_id' => $this->import->id, 
                'row' => $this->rowNumber
            ]);
            return;
        }
        
        try {
            Log::info("Processing product", [
                'import_id' => $this->import->id,
                'row' => $this->rowNumber,
                'product' => $this->productData['name'] ?? 'Unknown'
            ]);
            
            // Validate product data
            $validationResult = $this->validateProduct($this->productData);
            if (!$validationResult['valid']) {
                $this->recordFailure($validationResult['error'], 'validation');
                $this->incrementFailedCount();
                return;
            }
            
            // Prepare product data for API
            $productPayload = [
                'name' => $this->productData['name'],
                'description' => $this->productData['description'] ?? $this->productData['name'],
                'price' => (float) $this->productData['price'],
                'stockquantity' => (int) ($this->productData['stockquantity'] ?? 0),
                'barcode' => $this->productData['barcode'] ?? null,
                'category_id' => $this->productData['category_id'] ?? '7157739016492146860', // Default Childcare category
            ];
            
            // Create product in Cyclos
            $result = $service->createProduct($this->cyclosUsername, $productPayload);
            
            if ($result['success']) {
                $this->incrementSuccessCount();
                Log::info("Product created successfully", [
                    'import_id' => $this->import->id,
                    'row' => $this->rowNumber,
                    'product_id' => $result['product_id'],
                    'product_name' => $this->productData['name']
                ]);
            } else {
                $errorType = $this->determineErrorType($result['error']);
                $this->recordFailure($result['error'], $errorType);
                $this->incrementFailedCount();
                
                // If rate limit, release back to queue
                if ($errorType === 'rate_limit') {
                    $this->release(60);
                    return;
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Job failed", [
                'import_id' => $this->import->id,
                'row' => $this->rowNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->recordFailure($e->getMessage(), 'system');
            $this->incrementFailedCount();
            throw $e;
        }
        
        $this->updateProgress();
    }
    
    protected function validateProduct(array $data): array
    {
        if (empty($data['name'])) {
            return ['valid' => false, 'error' => 'Product name is required'];
        }
        
        if (!isset($data['price']) || $data['price'] <= 0) {
            return ['valid' => false, 'error' => 'Valid price is required (greater than 0)'];
        }
        
        if (strlen($data['name']) > 255) {
            return ['valid' => false, 'error' => 'Product name exceeds 255 characters'];
        }
        
        if (isset($data['stockquantity']) && $data['stockquantity'] < 0) {
            return ['valid' => false, 'error' => 'Stock quantity cannot be negative'];
        }
        
        return ['valid' => true];
    }
    
    protected function determineErrorType(string $errorMessage): string
    {
        $errorLower = strtolower($errorMessage);
        
        if (str_contains($errorLower, 'rate limit') || str_contains($errorLower, 'too many requests')) {
            return 'rate_limit';
        }
        if (str_contains($errorLower, 'network') || str_contains($errorLower, 'connection')) {
            return 'network';
        }
        if (str_contains($errorLower, 'duplicate') || str_contains($errorLower, 'already exists')) {
            return 'duplicate';
        }
        if (str_contains($errorLower, 'validation') || str_contains($errorLower, 'invalid')) {
            return 'validation';
        }
        
        return 'api';
    }
    
    protected function recordFailure(string $errorMessage, string $errorType): void
    {
        try {
            ImportFailureLog::create([
                'import_id' => $this->import->id,
                'user_id' => $this->import->user_id,
                'row_number' => $this->rowNumber,
                'product_name' => $this->productData['name'] ?? 'Unknown',
                'barcode' => $this->productData['barcode'] ?? null,
                'error_type' => $errorType,
                'error_message' => $errorMessage,
                'raw_data' => json_encode($this->productData),
                'resolved' => false,
                'retry_count' => 0
            ]);
            
            // Add to import error log
            $errorLog = $this->import->error_log ?? [];
            $errorLog[] = [
                'row' => $this->rowNumber,
                'product' => $this->productData['name'] ?? 'Unknown',
                'error' => $errorMessage,
                'timestamp' => now()->toIso8601String()
            ];
            
            // Keep last 500 errors
            if (count($errorLog) > 500) {
                $errorLog = array_slice($errorLog, -500);
            }
            
            $this->import->error_log = $errorLog;
            $this->import->save();
            
        } catch (\Exception $e) {
            Log::error("Failed to record failure", ['error' => $e->getMessage()]);
        }
    }
    
    protected function incrementSuccessCount(): void
    {
        Cache::increment("import:{$this->import->id}:success");
        Cache::increment("import:{$this->import->id}:processed");
    }
    
    protected function incrementFailedCount(): void
    {
        Cache::increment("import:{$this->import->id}:failed");
        Cache::increment("import:{$this->import->id}:processed");
    }
    
    protected function updateProgress(): void
    {
        $processed = Cache::get("import:{$this->import->id}:processed", 0);
        $success = Cache::get("import:{$this->import->id}:success", 0);
        $failed = Cache::get("import:{$this->import->id}:failed", 0);
        
        $this->import->updateProgress($processed, $success, $failed);
    }
}