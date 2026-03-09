<?php
// App\Models\UserGovernanceLayer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGovernanceLayer extends Model
{
    protected $table = 'user_governance_layers';
    
    protected $fillable = ['user_id', 'governance_id', 'status','message','representation_id'];

    public function governance()
    {
        return $this->belongsTo(GovernanceLayer::class, 'governance_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope for approved layers
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Scope for rejected layers
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Scope for pending layers
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}