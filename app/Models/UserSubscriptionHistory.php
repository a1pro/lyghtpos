<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscriptionHistory extends Model
{
    protected $table = 'user_subscription_histories';
    
    protected $fillable = [
        'user_id',
        'plan_id',
        'user_subscription_id',
        'billing_type',
        'start_date',
        'end_date',
        'transaction_id',
        'amount_paid',
        'payment_method',
        'status',
        'changed_to_plan_id',
        'changed_at'
    ];
    
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'changed_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }
    
    public function changedToPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'changed_to_plan_id');
    }
}