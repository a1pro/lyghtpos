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
        'tax_amount',
        'card_amount',
        'card_surcharge_amount',
        'token_amount',
        'total_amount',
        'payment_method',
        'pos_mobile_payment_method',
        'payment_type',
        'status',
        'payment_status',
        'notes',
        'transaction_id',
        'card_transaction_id',
        'cyclos_transaction_id',
        'paid_at',
        'cyclos_order_id',
        'payment_metadata',

        'handpoint_aid',
        'handpoint_arc',
        'handpoint_issuer_response_code',
        'handpoint_rrn',
        'handpoint_authorization_code',
        'handpoint_card_last4',
        'handpoint_card_brand',
        'handpoint_transaction_reference',
        'handpoint_fin_status',

        'procharge_authorization_number',
        'procharge_card_token',
        'procharge_transaction_id',
        'procharge_invoice_id',
        'procharge_response',

        'refund_mode',
        'refund_status',
        'refund_notes',
        'refund_procharge_txn_id',
        'refund_procharge_response',
        'refund_cyclos_token_txn_id',
        'refund_cyclos_token_response',

        'failed_at',
        'failure_message',
    ];

    protected $casts = [
        'tax_amount' => 'decimal:2',
        'card_amount' => 'decimal:2',
        'card_surcharge_amount' => 'decimal:2',
        'token_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_metadata' => 'array',
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
