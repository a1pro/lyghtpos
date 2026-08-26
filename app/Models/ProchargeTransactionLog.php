<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProchargeTransactionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_identifier',
        'authorization_number',
        'invoice_id',
        'payment_id',
        'batch_id',
        'batch_number',
        
        'card_type',
        'card_last_four',
        'card_scheme',
        
        'amount',
        'currency',
        
        'response_code',
        'response_text',
        'network_response_code',
        'host_response',
        
        'card_token',
        'order_id',
        'customer_id',
        
        'status',
        
        'request_payload',
        'response_payload',
        
        'environment',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}