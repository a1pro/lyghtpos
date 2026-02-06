<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Representation extends Model
{
    protected $fillable = ['user_id','subject','purpose','futuristic_plan','pledge_value','currency'];
}
