<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantProductImport extends Model
{
    protected $fillable = [
        'user_id',
        'cyclos_import_id',
        'file_name'
    ];
}
