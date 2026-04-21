<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPlanPurchase extends Model
{
    protected $fillable = ['user_id','plan_id','billing_type','start_date','end_date','transaction_id','amount_paid','payment_method','payment_status','paypal_payment_id','paypal_payer_id','paypal_sale_id','paypal_order_id','capture_id','status'];



    public function plan()
    {
    	return $this->belongsTo(CustomerPlans::class,'plan_id');
    }


    public function user()
    {
    	return $this->belongsTo(User::class,'user_id');
    }

    public function scopeActive($query)
    {
    	return $query->where('status','active')->where('end_date','>',now());
    }


    public function isValid()
    {
    	return $this->status=='active' && $this->end_date > now();
    }
}
