<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'billing_type',
        'start_date',
        'end_date',
        'transaction_id',
        'amount_paid',
        'payment_method',
        'payment_status',
        'paypal_payment_id',
        'paypal_payer_id',
        'paypal_sale_id',
        'status',
        'paypal_order_id',
        'paypal_capture_id'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'amount_paid' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function isActive()
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }

    public function isExpired()
    {
        return $this->end_date->isPast();
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }
}