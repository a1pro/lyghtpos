<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPlans extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'badge_color',
        'badge_text',
        'short_description',
        'description',
        'monthly_price',
        'yearly_price',
        'yearly_total',
        'features',
        'sort_order',
        'is_popular',
        'is_active'
    ];

    protected $casts = [
        'features' => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'yearly_total' => 'decimal:2'
    ];


    public function purchases()
    {
        return $this->hasMany(CustomerPlanPurchase::class,'plan_id');
    }

    public function activePurchases()
    {
        return $this->hasMany(CustomerPlanPurchase::class, 'plan_id')
                    ->where('status', 'active')
                    ->where('end_date', '>', now()); 
    }
}
