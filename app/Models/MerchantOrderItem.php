<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantOrderItem extends Model
{
    protected $table = 'merchant_order_items';

    protected $fillable = [
        'merchant_order_id',
        'product_cyclos_id',
        'product_name',
        'price',
        'quantity',
        'meta',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'meta' => 'array',
    ];

    /**
     * Get the order that owns this item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(MerchantOrder::class, 'merchant_order_id');
    }
}
