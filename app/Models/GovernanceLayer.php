<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovernanceLayer extends Model
{
    protected $fillable = ['name', 'description', 'status'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_governance_layers')->withPivot('status')->withTimeStamps();
    }
}
