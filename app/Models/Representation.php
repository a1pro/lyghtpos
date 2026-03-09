<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Representation extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'purpose',
        'futuristic_plan',
        'pledge_value',
        'currency',
        'status',
        'review_message',
        'last_reviewed',
        'reviewer_user_id',
        'payment_status'
    ];

    protected $casts = [
        'last_reviewed' => 'datetime',
    ];

    /**
     * Get the user who created this representation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get merchant through user relationship
     */
    public function merchant()
    {
        return $this->user->merchant();
    }

    /**
     * Get merchant details if user is a merchant
     */
    public function getMerchantRecordAttribute()
    {
        if ($this->user && $this->user->merchant) {
            return $this->user->merchant;
        }
        return null;
    }

    /**
     * Get the reviewer who reviewed this representation.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    /**
     * Get the review logs for this representation.
     */
    public function reviewLogs(): HasMany
    {
        return $this->hasMany(RepresentationReviewLog::class);
    }

    /**
     * Get the latest review update.
     */
    public function latestReviewLog()
    {
        return $this->hasOne(RepresentationReviewLog::class)->latest();
    }
}
