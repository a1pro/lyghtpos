<?php
// app/Models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_id',
        'merchant_id',
        'merchant_cyclos_id',
        'customer_id',
        'customer_cyclos_id',
        'total_amount',
        'platform_fee',
        'amount_after_platform_fee',
        'admin_share',
        'merchant_share',
        'payment_method',
        'payment_status',
        'cart_data',
        'payment_response',
        'fee_breakdown',
        'paid_at',
        'received_amount',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'amount_after_platform_fee' => 'decimal:2',
        'admin_share' => 'decimal:2',
        'merchant_share' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'cart_data' => 'array',
        'payment_response' => 'array',
        'fee_breakdown' => 'array',
        'paid_at' => 'datetime',
    ];

    // Accessors for formatted amounts
    public function getFormattedTotalAmountAttribute(): string
    {
        return number_format($this->total_amount, 2);
    }

    public function getFormattedPlatformFeeAttribute(): string
    {
        return number_format($this->platform_fee, 2);
    }

    public function getFormattedMerchantShareAttribute(): string
    {
        return number_format($this->merchant_share, 2);
    }

    public function getFormattedAdminShareAttribute(): string
    {
        return number_format($this->admin_share, 2);
    }

    public function getFormattedReceivedAmountAttribute(): string
    {
        return $this->received_amount ? number_format($this->received_amount, 2) : 'N/A';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        $classes = [
            'pending' => 'badge-warning',
            'completed' => 'badge-success',
            'failed' => 'badge-danger',
            'refunded' => 'badge-info',
        ];

        return $classes[$this->payment_status] ?? 'badge-secondary';
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ];

        return $labels[$this->payment_status] ?? ucfirst($this->payment_status);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        $labels = [
            'card' => 'Credit/Debit Card',
            'cash' => 'Cash',
            'unyti' => 'Unyti',
        ];

        return $labels[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    // Scopes for querying
    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    public function scopeCardPayments($query)
    {
        return $query->where('payment_method', 'card');
    }

    public function scopeCashPayments($query)
    {
        return $query->where('payment_method', 'cash');
    }

    public function scopeUnytiPayments($query)
    {
        return $query->where('payment_method', 'unyti');
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeForMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Helper methods for calculations
    public static function getTotalFeesForMerchant($merchantId, $startDate = null, $endDate = null)
    {
        $query = self::where('merchant_id', $merchantId)
                     ->where('payment_status', 'completed');
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        return [
            'total_platform_fee' => $query->sum('platform_fee'),
            'total_merchant_share' => $query->sum('merchant_share'),
            'total_admin_share' => $query->sum('admin_share'),
            'total_transactions' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
            'average_amount' => $query->avg('total_amount'),
        ];
    }

    public static function getSummaryByPaymentMethod($merchantId = null, $startDate = null, $endDate = null)
    {
        $query = self::where('payment_status', 'completed');
        
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        return $query->selectRaw('payment_method, 
            COUNT(*) as count, 
            SUM(total_amount) as total_amount,
            SUM(platform_fee) as total_platform_fee,
            SUM(merchant_share) as total_merchant_share,
            SUM(admin_share) as total_admin_share')
            ->groupBy('payment_method')
            ->get();
    }

    // Relationships
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}