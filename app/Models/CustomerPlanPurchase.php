<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPlanPurchase extends Model
{
    protected $fillable = ['user_id','plan_id','billing_type','start_date','end_date','transaction_id','amount_paid','payment_method','payment_status','paypal_payment_id','paypal_payer_id','paypal_sale_id','paypal_order_id','capture_id','status'];
}
