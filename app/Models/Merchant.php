<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    protected $fillable = [
        'user_id',
        'merchant_id',
        'business_name',
        'primary_contact_name',
        'phone',
        'location',
        'website',
        'consent_language_authorizing',
        'communyti_enrollment',
        'advocate_contact'
    ];
}
