<?php
// app/Repositories/Merchant/Interfaces/TeamMemberRepositoryInterface.php

namespace App\Repositories\Merchant\Interfaces;

use App\Models\User;

interface TeamMemberRepositoryInterface
{
    public function all($merchantId);
    public function paginate($merchantId, $perPage = 10);
    public function find($id);
    public function create(array $data);
    public function update(User $teamMember, array $data);
    public function delete(User $teamMember);
}