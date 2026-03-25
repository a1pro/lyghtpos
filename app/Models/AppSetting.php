<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['group', 'name', 'key', 'value', 'environment'];
    
    protected $casts = [
        'value' => 'json', // In case you want to store JSON values
    ];
}