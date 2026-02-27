<?php

namespace App\Repositories\Admin\Interfaces;

use App\Models\GovernanceLayer;

interface GovernanceLayerRepositoryInterface
{
    public function all();
    public function create(array $data);
    public function update(GovernanceLayer $layer, array $data);
    public function delete(GovernanceLayer $layer);
}