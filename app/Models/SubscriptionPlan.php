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
        'is_active',
        'products_limit',
        'customer_limit',
        'yearly_products_limit',
        'yearly_customer_limit',
        'upgraded_from_plan_id',
                'upgraded_from_subscription_id',
                'upgraded_to_plan_id',
                'upgraded_at',
                'prorated_amount'
    ];

    protected $casts = [
        'features' => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'yearly_total' => 'decimal:2'
    ];

    public function getFeaturesAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        if (is_array($value)) {
            if (count($value) === 1 && is_string($value[0]) && (strpos($value[0], "\n") !== false || strpos($value[0], "\r") !== false)) {
                $split = preg_split('/\r\n|\r|\n/', $value[0]);
                return array_filter(array_map('trim', $split));
            }
            return $value;
        }

        $decoded = json_decode($value, true);

        if (!is_array($decoded)) {
            return [];
        }

        if (count($decoded) === 1 && is_string($decoded[0]) && (strpos($decoded[0], "\n") !== false || strpos($decoded[0], "\r") !== false)) {
            $split = preg_split('/\r\n|\r|\n/', $decoded[0]);
            return array_filter(array_map('trim', $split));
        }

        return $decoded;
    }

    public function setFeaturesAttribute($value)
    {
        if (is_string($value)) {
            $split = preg_split('/\r\n|\r|\n/', $value);
            $value = array_filter(array_map('trim', $split));
        }

        if (is_array($value)) {
            if (count($value) === 1 && is_string($value[0]) && (strpos($value[0], "\n") !== false || strpos($value[0], "\r") !== false)) {
                $split = preg_split('/\r\n|\r|\n/', $value[0]);
                $value = array_filter(array_map('trim', $split));
            }
            $this->attributes['features'] = json_encode(array_values($value));
        } else {
            $this->attributes['features'] = $value;
        }
    }

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
