<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserJoiningFees extends Model
{
    protected $fillable = ['user_id','amount','transaction_id','status'];
}
