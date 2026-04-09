<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantOrder extends Model
{
    protected $table = 'merchant_orders';

    protected $fillable = [
        'order_number',
        'merchant_id',
        'customer_id',
        'customer_cyclos_id',
        'total_amount',
        'payment_method',
        'status',
        'payment_status',
        'notes',
        'transaction_id',
        'paid_at'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the items for this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MerchantOrderItem::class, 'merchant_order_id');
    }

    /**
     * Get the merchant (user) who owns this order.
     */
    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }
}
