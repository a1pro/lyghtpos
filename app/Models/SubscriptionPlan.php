<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

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

    /**
     * Get formatted monthly price with currency
     */
    public function getFormattedMonthlyPriceAttribute()
    {
        return '$' . number_format($this->monthly_price, 0);
    }

    /**
     * Get formatted yearly price with currency
     */
    public function getFormattedYearlyPriceAttribute()
    {
        return '$' . number_format($this->yearly_price, 0);
    }

    /**
     * Get formatted yearly total with currency
     */
    public function getFormattedYearlyTotalAttribute()
    {
        return '$' . number_format($this->yearly_total ?? ($this->yearly_price * 12), 0);
    }

    /**
     * Calculate yearly savings percentage
     */
    public function getYearlySavingsAttribute()
    {
        $monthlyTotal = $this->monthly_price * 12;
        $yearlyTotal = $this->yearly_total ?? ($this->yearly_price * 12);

        if ($monthlyTotal > 0) {
            $savings = (($monthlyTotal - $yearlyTotal) / $monthlyTotal) * 100;
            return round($savings);
        }

        return 0;
    }
}
