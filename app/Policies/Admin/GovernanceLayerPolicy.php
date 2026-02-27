<?php
namespace App\Policies\Admin;

use App\Models\User;
use App\Models\GovernanceLayer;

class GovernanceLayerPolicy
{

    public function before(User $user)
    {
        return $user->role_id == '1' ? true : null;
    }
    public function ViewAny(User $user){
        return false;
    }

    public function view(User $user, GovernanceLayer $governanceLayer)
    {
        return false;
    }

    public function create(User $user)
    {
        return false;
    }

    public function update(User $user, GovernanceLayer $governanceLayer)
    {
        return false;
    }

    public function delete(User $user, GovernanceLayer $governanceLayer)
    {
        return false;
    }

     public function restore(User $user, GovernanceLayer $governanceLayer)
    {
        return false;
    }
}