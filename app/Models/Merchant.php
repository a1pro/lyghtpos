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
        'vat_tax_percentage',
        'card_surcharge_percentage',
        'pos_payment_types',
        'consent_language_authorizing',
        'communyti_enrollment',
        'advocate_contact'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
