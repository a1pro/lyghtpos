<?php
// app/Jobs/RetryProductImport.php

namespace App\Jobs;

use App\Models\ImportFailureLog;
use App\Models\MerchantProductImport;
use App\Services\Merchant\MerchantProductImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RetryProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 120;
    public $tries = 3;
    
    protected $failureId;
    
    public function __construct(int $failureId)
    {
        $this->failureId = $failureId;
    }
    
    public function handle(MerchantProductImportService $service)
    {
        $failure = ImportFailureLog::find($this->failureId);
        
        if (!$failure || $failure->resolved) {
            return;
        }
        
        // Don't retry validation or duplicate errors
        if ($failure->error_type === 'validation' || $failure->error_type === 'duplicate') {
            return;
        }
        
        if ($failure->retry_count >= 3) {
            Log::info("Max retries reached for failure", ['failure_id' => $this->failureId]);
            return;
        }
        
        $import = $failure->import;
        
        if ($import->status === MerchantProductImport::STATUS_FAILED) {
            return;
        }
        
        $rawData = $failure->raw_data ?? [];
        
        // Get cyclos username from import user
        $cyclosUsername = $import->user->cyclos_username ?? null;
        
        if (empty($cyclosUsername)) {
            $failure->error_message = "Cannot retry: Missing Cyclos username";
            $failure->save();
            return;
        }
        
        try {
            $productData = [
                'name' => $rawData['name'] ?? $failure->product_name,
                'description' => $rawData['description'] ?? $failure->product_name,
                'price' => (float) ($rawData['price'] ?? 0),
                'stockquantity' => (int) ($rawData['stockquantity'] ?? 0),
                'barcode' => $failure->barcode,
            ];
            
            $result = $service->createProduct($cyclosUsername, $productData);
            
            if ($result['success']) {
                $failure->resolved = true;
                $failure->save();
                
                Cache::increment("import:{$import->id}:success");
                Cache::increment("import:{$import->id}:processed");
                
                Log::info("Retry successful", [
                    'import_id' => $import->id,
                    'failure_id' => $this->failureId
                ]);
            } else {
                $failure->retry_count++;
                $failure->error_message = "Retry {$failure->retry_count}: " . $result['error'];
                $failure->save();
                
                if ($failure->retry_count < 3) {
                    self::dispatch($this->failureId)->delay(now()->addMinutes(5));
                }
            }
            
        } catch (\Exception $e) {
            $failure->retry_count++;
            $failure->error_message = "Retry {$failure->retry_count}: " . $e->getMessage();
            $failure->save();
            
            if ($failure->retry_count < 3) {
                self::dispatch($this->failureId)->delay(now()->addMinutes(5));
            }
        }
        
        // Update progress
        $processed = Cache::get("import:{$import->id}:processed", 0);
        $success = Cache::get("import:{$import->id}:success", 0);
        $failed = Cache::get("import:{$import->id}:failed", 0);
        $import->updateProgress($processed, $success, $failed);
    }
}