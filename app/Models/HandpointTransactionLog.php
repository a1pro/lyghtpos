<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandpointTransactionLog extends Model
{
    protected $table = 'handpoint_transaction_logs';

    protected $fillable = [
        'merchant_order_id',
        'merchant_id',
        'customer_id',
        'transaction_id',
        'transaction_reference',
        'status',
        'fin_status',
        'status_message',
        'amount',
        'currency',
        'tip_amount',
        'approved_amount',
        'requested_amount',
        'aid',
        'arc',
        'issuer_response_code',
        'rrn',
        'authorization_code',
        'card_last4',
        'card_brand',
        'masked_card_number',
        'card_expiry',
        'tender_type',
        'device_serial',
        'device_status_message',
        'device_battery_status',
        'merchant_receipt_url',
        'customer_receipt_url',
        'is_partial_approval',
        'is_recovered',
        'is_reversal_attempted',
        'raw_response',
        'additional_data',
        'transaction_timestamp',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tip_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'requested_amount' => 'decimal:2',
        'is_partial_approval' => 'boolean',
        'is_recovered' => 'boolean',
        'is_reversal_attempted' => 'boolean',
        'raw_response' => 'array',
        'additional_data' => 'array',
        'transaction_timestamp' => 'datetime',
    ];

    /**
     * Get the order associated with this transaction
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(MerchantOrder::class, 'merchant_order_id');
    }

    /**
     * Get the merchant associated with this transaction
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    /**
     * Get the customer associated with this transaction
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Scope for successful transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success')->where('fin_status', 'AUTHORISED');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed')->orWhere('fin_status', 'DECLINED');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope by device serial
     */
    public function scopeByDevice($query, $deviceSerial)
    {
        return $query->where('device_serial', $deviceSerial);
    }

    /**
     * Get formatted card display
     */
    public function getFormattedCardAttribute(): string
    {
        if ($this->card_brand && $this->card_last4) {
            return "{$this->card_brand} **** {$this->card_last4}";
        }
        if ($this->masked_card_number) {
            return $this->masked_card_number;
        }
        return 'N/A';
    }

    /**
     * Get transaction status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->fin_status) {
            'AUTHORISED' => 'success',
            'DECLINED' => 'danger',
            'PENDING' => 'warning',
            default => 'secondary',
        };
    }
}
