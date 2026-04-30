<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserSubscription extends Model
{
    use HasFactory;

    protected $table = 'user_subscriptions';

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
        'paypal_order_id',
        'paypal_capture_id',
        'status'
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
        return $this->status === 'active' && $this->end_date && $this->end_date->isFuture();
    }

    public function isExpired()
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }
    
    public function isTrialing()
    {
        return $this->status === 'trialing';
    }
    
    public function daysRemaining()
    {
        if (!$this->end_date) {
            return 0;
        }
        
        $days = now()->diffInDays($this->end_date, false);
        return $days > 0 ? $days : 0;
    }
    
    public function getRemainingDaysAttribute()
    {
        return $this->daysRemaining();
    }
    
    public function getRemainingValueAttribute()
    {
        if (!$this->end_date || $this->end_date->isPast()) {
            return 0;
        }
        
        $totalDays = $this->start_date->diffInDays($this->end_date);
        $usedDays = $this->start_date->diffInDays(now());
        $remainingDays = $totalDays - $usedDays;
        
        if ($remainingDays <= 0) {
            return 0;
        }
        
        $dailyRate = $this->amount_paid / $totalDays;
        return round($dailyRate * $remainingDays, 2);
    }
    
    public function canUpgradeTo(SubscriptionPlan $newPlan)
    {
        // Check if new plan is actually higher tier (you may have a 'level' field in SubscriptionPlan)
        // For now, we'll compare prices
        $currentPrice = $this->billing_type === 'monthly' 
            ? $this->plan->monthly_price 
            : $this->plan->yearly_price;
            
        $newPrice = $this->billing_type === 'monthly' 
            ? $newPlan->monthly_price 
            : $newPlan->yearly_price;
            
        return $newPrice > $currentPrice;
    }
    
    public function canDowngradeTo(SubscriptionPlan $newPlan)
    {
        $currentPrice = $this->billing_type === 'monthly' 
            ? $this->plan->monthly_price 
            : $this->plan->yearly_price;
            
        $newPrice = $this->billing_type === 'monthly' 
            ? $newPlan->monthly_price 
            : $newPlan->yearly_price;
            
        return $newPrice < $currentPrice;
    }
}