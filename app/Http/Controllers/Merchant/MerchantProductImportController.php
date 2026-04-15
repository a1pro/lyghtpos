<?php
// app/Http/Controllers/Merchant/MerchantProductImportController.php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\ProductImportRequest;
use App\Models\MerchantProductImport;
use App\Models\ImportFailureLog;
use App\Services\Merchant\MerchantProductImportService;
use App\Jobs\ProcessProductImport;
use App\Jobs\RetryProductImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MerchantProductImportController extends Controller
{
    protected $service;

    public function __construct(MerchantProductImportService $service)
    {
        $this->service = $service;
    }

    /**
     * Display import history
     */
    public function index()
    {
        $imports = MerchantProductImport::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('merchant.pages.products.import-list', compact('imports'));
    }

    /**
     * Show upload form
     */
    public function create()
    {
        // Check for active import
        $activeImport = MerchantProductImport::where('user_id', Auth::id())
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        return view('merchant.pages.products.import', compact('activeImport'));
    }

    /**
     * Process uploaded CSV and dispatch queue jobs
     */
    public function store(ProductImportRequest $request)
    {
        try {
            $file = $request->file('file');
            $merchant = Auth::user();
            
            // Check for active import
            $activeImport = MerchantProductImport::where('user_id', $merchant->id)
                ->whereIn('status', ['pending', 'processing'])
                ->first();
                
            if ($activeImport) {
                return back()->with('error', 'You already have an active import. Please wait for it to complete.');
            }
            
            // Process the import and dispatch jobs
            $result = $this->service->processImport($file, $merchant);
            
            return redirect()
                ->route('merchant.products.import.show', $result['cyclos_import_id'])
                ->with('success', sprintf(
                    'Import started! %d products queued. Estimated time: %s',
                    $result['total_products'],
                    $result['estimated_time_formatted']
                ));
                
        } catch (\Exception $e) {
            Log::error('Import failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Show import details with real-time updates
     */
    public function show($cyclosImportId)
    {
        // Get local import record by cyclos_import_id
        $import = MerchantProductImport::where('user_id', Auth::id())
            ->where('cyclos_import_id', $cyclosImportId)
            ->firstOrFail();
        
        // Get Cyclos import details
        $cyclosData = $this->service->getImportDetails($cyclosImportId);
        
        // Get local failures
        $failures = ImportFailureLog::where('import_id', $import->id)
            ->where('resolved', false)
            ->orderBy('row_number')
            ->paginate(20);
        
        // Get error summary
        $errorSummary = $this->getErrorSummary($import->id);
        
        // Get estimated time
        $estimatedTime = $import->getEstimatedTimeRemaining();
        
        // Get progress from cache
        $progress = [
            'linesImported' => $import->success_rows,
            'linesImportError' => $import->failed_rows,
            'linesValidationError' => $errorSummary['validation'],
            'linesSkipped' => $errorSummary['duplicate'],
            'linesReady' => $import->processed_rows,
            'progress' => $import->getProgressPercentage() / 100
        ];
        
        // Merge Cyclos data with local progress
        $data = array_merge($cyclosData, [
            'progress' => $progress,
            'local_status' => $import->status,
            'local_success' => $import->success_rows,
            'local_failed' => $import->failed_rows,
            'local_processed' => $import->processed_rows,
            'local_total' => $import->total_rows,
            'estimated_time_remaining' => $estimatedTime ? $estimatedTime['formatted'] : null,
            'estimated_completion' => $estimatedTime ? $estimatedTime['estimated_completion'] : null,
            'can_cancel' => $import->canBeCancelled(),
            'import_id' => $import->id,
            'id' => $cyclosImportId,  // Make sure this is set
            'cyclos_import_id' => $cyclosImportId  // Make sure this is set
        ]);
        
        return view('merchant.pages.products.import-result', compact('data', 'import', 'failures', 'errorSummary'));
    }
    
    
    /**
     * API endpoint for real-time progress updates
     */
    public function progress($cyclosImportId)
    {
        $import = MerchantProductImport::where('user_id', Auth::id())
            ->where('cyclos_import_id', $cyclosImportId)
            ->firstOrFail();
        
        $estimatedTime = $import->getEstimatedTimeRemaining();
        $errorSummary = $this->getErrorSummary($import->id);
        
        return response()->json([
            'id' => $import->id,
            'cyclos_import_id' => $import->cyclos_import_id,
            'status' => $import->status,
            'processed' => $import->processed_rows,
            'success' => $import->success_rows,
            'failed' => $import->failed_rows,
            'total' => $import->total_rows,
            'percentage' => $import->getProgressPercentage(),
            'validation_errors' => $errorSummary['validation'],
            'api_errors' => $errorSummary['api'],
            'duplicates' => $errorSummary['duplicate'],
            'estimated_time_remaining' => $estimatedTime ? $estimatedTime['formatted'] : null,
            'estimated_completion' => $estimatedTime ? $estimatedTime['estimated_completion'] : null,
            'can_cancel' => $import->canBeCancelled(),
            'is_complete' => in_array($import->status, ['completed', 'partial', 'failed'])
        ]);
    }
    
    /**
     * Cancel import
     */
    public function cancel($cyclosImportId)
    {
        $import = MerchantProductImport::where('user_id', Auth::id())
            ->where('cyclos_import_id', $cyclosImportId)
            ->firstOrFail();
        
        if (!$import->canBeCancelled()) {
            return response()->json(['error' => 'Import cannot be cancelled'], 422);
        }
        
        $import->status = MerchantProductImport::STATUS_FAILED;
        $import->completed_at = now();
        $errorLog = $import->error_log ?? [];
        $errorLog[] = [
            'error' => 'Import cancelled by user',
            'timestamp' => now()->toIso8601String()
        ];
        $import->error_log = $errorLog;
        $import->save();
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Retry failed products
     */
    public function retryFailed($cyclosImportId)
    {
        $import = MerchantProductImport::where('user_id', Auth::id())
            ->where('cyclos_import_id', $cyclosImportId)
            ->firstOrFail();
        
        $failures = ImportFailureLog::where('import_id', $import->id)
            ->where('resolved', false)
            ->where('error_type', '!=', 'validation')
            ->where('error_type', '!=', 'duplicate')
            ->get();
        
        if ($failures->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No retryable failures found'
            ]);
        }
        
        $retryCount = 0;
        foreach ($failures as $failure) {
            RetryProductImport::dispatch($failure->id);
            $retryCount++;
        }
        
        return response()->json([
            'success' => true,
            'message' => "{$retryCount} products queued for retry"
        ]);
    }
    
    /**
     * Download error report
     */
    public function downloadErrors($cyclosImportId)
    {
        $import = MerchantProductImport::where('user_id', Auth::id())
            ->where('cyclos_import_id', $cyclosImportId)
            ->firstOrFail();
        
        $failures = ImportFailureLog::where('import_id', $import->id)
            ->where('resolved', false)
            ->get();
        
        $filename = "import_errors_{$import->id}_" . date('Ymd_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];
        
        $callback = function() use ($failures) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Row', 'Product Name', 'Barcode', 'Error Type', 'Error Message']);
            
            foreach ($failures as $failure) {
                fputcsv($file, [
                    $failure->row_number,
                    $failure->product_name,
                    $failure->barcode,
                    $failure->error_type,
                    $failure->error_message
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Get error summary for import
     */
    protected function getErrorSummary($importId)
    {
        $errors = ImportFailureLog::where('import_id', $importId)
            ->select('error_type', \DB::raw('count(*) as count'))
            ->groupBy('error_type')
            ->pluck('count', 'error_type')
            ->toArray();
            
        return [
            'validation' => $errors['validation'] ?? 0,
            'api' => $errors['api'] ?? 0,
            'network' => $errors['network'] ?? 0,
            'duplicate' => $errors['duplicate'] ?? 0,
            'other' => $errors['other'] ?? 0,
            'total' => array_sum($errors)
        ];
    }
}