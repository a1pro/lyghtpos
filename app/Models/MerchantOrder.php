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

    /**
     * Get the customer (user) for this order using cyclos_id.
     * This relates customer_cyclos_id in merchant_orders to cyclos_user_id in users table.
     */
    public function customer()
    {
        $customer = User::where('cyclos_user_id', $this->customer_cyclos_id)->first();

        if (!$customer && $this->customer_id) {
            $customer = User::find($this->customer_id);
        }

        return $customer;
    }
}
