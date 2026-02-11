<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepresentationReviewLog extends Model
{
    protected $fillable = [
        'user_id',
        'merchant_id',
        'representation_id',
        'before_status',
        'after_status',
        'message',
    ];

    /**
     * Get the user who created this representation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
