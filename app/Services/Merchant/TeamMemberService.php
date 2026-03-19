<?php
// app/Services/Merchant/TeamMemberService.php

namespace App\Services\Merchant;

use App\Models\User;
use App\Repositories\Merchant\Interfaces\TeamMemberRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TeamMemberService
{
    public function __construct(
        private TeamMemberRepositoryInterface $repo
    ) {}

    public function getAll()
    {
        $merchantId = Auth::id();
        return $this->repo->all($merchantId);
    }

    public function getAllPaginated($perPage = 10)
    {
        $merchantId = Auth::id();
        return $this->repo->paginate($merchantId, $perPage);
    }

    public function create(array $data)
    {
        $data['merchant_id'] = Auth::id();
        $data['password'] = Hash::make($data['password']);
        
        return $this->repo->create($data);
    }

    public function update(User $teamMember, array $data)
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        
        return $this->repo->update($teamMember, $data);
    }

    public function delete(User $teamMember)
    {
        return $this->repo->delete($teamMember);
    }

    public function find($id)
    {
        $teamMember = $this->repo->find($id);
        
        // Ensure the team member belongs to the logged-in merchant
        if ($teamMember->merchant_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        
        return $teamMember;
    }
}