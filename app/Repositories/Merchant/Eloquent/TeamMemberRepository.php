<?php
// app/Repositories/Merchant/Eloquent/TeamMemberRepository.php

namespace App\Repositories\Merchant\Eloquent;

use App\Models\User;
use App\Repositories\Merchant\Interfaces\TeamMemberRepositoryInterface;

class TeamMemberRepository implements TeamMemberRepositoryInterface
{
    public function all($merchantId)
    {
        return User::where('merchant_id', $merchantId)
            ->whereIn('role_id', [9, 10])
            ->latest()
            ->get();
    }

    public function paginate($merchantId, $perPage = 10)
    {
        return User::where('merchant_id', $merchantId)
            ->whereIn('role_id', [9, 10])
            ->latest()
            ->paginate($perPage);
    }

    public function find($id)
    {
        return User::whereIn('role_id', [9, 10])->findOrFail($id);
    }

    public function create(array $data)
    {
        return User::create($data);
    }

    public function update(User $teamMember, array $data)
    {
        $teamMember->update($data);
        return $teamMember;
    }

    public function delete(User $teamMember)
    {
        return $teamMember->delete();
    }
}