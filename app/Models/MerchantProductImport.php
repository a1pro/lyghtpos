<?php
// app/Models/MerchantProductImport.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class MerchantProductImport extends Model
{
    protected $table = 'merchant_product_imports';
    
    // Add status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_PARTIAL = 'partial';
    
    protected $fillable = [
        'user_id', 'cyclos_import_id', 'file_name', 'total_rows',
        'processed_rows', 'success_rows', 'failed_rows', 'status',
        'error_log', 'started_at', 'completed_at'
    ];
    
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'error_log' => 'array',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'success_rows' => 'integer',
        'failed_rows' => 'integer',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function failures(): HasMany
    {
        return $this->hasMany(ImportFailureLog::class, 'import_id');
    }
    
    public function updateProgress(int $processed, int $success, int $failed): void
    {
        $this->processed_rows = $processed;
        $this->success_rows = $success;
        $this->failed_rows = $failed;
        
        if ($this->processed_rows >= $this->total_rows && $this->total_rows > 0) {
            $this->status = $this->failed_rows > 0 ? self::STATUS_PARTIAL : self::STATUS_COMPLETED;
            $this->completed_at = now();
        }
        
        $this->save();
        
        // Update cache for real-time monitoring
        Cache::put("import:{$this->id}:progress", [
            'processed' => $processed,
            'success' => $success,
            'failed' => $failed,
            'percentage' => $this->getProgressPercentage(),
            'status' => $this->status
        ], now()->addHours(24));
    }
    
    public function getProgressPercentage(): float
    {
        if ($this->total_rows <= 0) {
            return 0;
        }
        return round(($this->processed_rows / $this->total_rows) * 100, 2);
    }
    
    public function getEstimatedTimeRemaining(): ?array
    {
        if ($this->processed_rows <= 0 || $this->processed_rows >= $this->total_rows) {
            return null;
        }
        
        $elapsedSeconds = $this->started_at ? $this->started_at->diffInSeconds(now()) : 0;
        
        if ($elapsedSeconds <= 0 || $this->processed_rows <= 0) {
            return null;
        }
        
        $secondsPerProduct = $elapsedSeconds / $this->processed_rows;
        $remainingRows = $this->total_rows - $this->processed_rows;
        $remainingSeconds = $remainingRows * $secondsPerProduct;
        
        return [
            'seconds' => $remainingSeconds,
            'formatted' => $this->formatDuration($remainingSeconds),
            'estimated_completion' => now()->addSeconds($remainingSeconds)->format('g:i A')
        ];
    }
    
    protected function formatDuration($seconds): string
    {
        if ($seconds < 60) {
            return round($seconds) . ' seconds';
        } elseif ($seconds < 3600) {
            return round($seconds / 60, 1) . ' minutes';
        } else {
            return round($seconds / 3600, 1) . ' hours';
        }
    }
    
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }
    
    public function getErrorSummary(): array
    {
        $errors = $this->failures()
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