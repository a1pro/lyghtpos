<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
