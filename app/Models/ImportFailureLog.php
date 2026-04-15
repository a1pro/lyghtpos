<?php
// app/Models/ImportFailureLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportFailureLog extends Model
{
    protected $table = 'import_failure_logs';
    public $timestamps = false;
    
    protected $fillable = [
        'import_id', 'user_id', 'row_number', 'product_name', 'barcode',
        'error_type', 'error_message', 'raw_data', 'resolved', 'retry_count'
    ];
    
    protected $casts = [
        'raw_data' => 'array',
        'resolved' => 'boolean',
        'retry_count' => 'integer',
    ];
    
    const ERROR_TYPE_VALIDATION = 'validation';
    const ERROR_TYPE_API = 'api';
    const ERROR_TYPE_NETWORK = 'network';
    const ERROR_TYPE_DUPLICATE = 'duplicate';
    const ERROR_TYPE_OTHER = 'other';
    
    public function import(): BelongsTo
    {
        return $this->belongsTo(MerchantProductImport::class, 'import_id');
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function incrementRetry(): void
    {
        $this->retry_count++;
        $this->save();
    }
    
    public function markResolved(): void
    {
        $this->resolved = true;
        $this->save();
    }
    
    public function isRetryable(): bool
    {
        return $this->error_type !== self::ERROR_TYPE_VALIDATION 
            && $this->error_type !== self::ERROR_TYPE_DUPLICATE
            && $this->retry_count < 3;
    }
}