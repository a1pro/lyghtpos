<?php

namespace App\Services\Admin;

use App\Models\GovernanceLayer;
use App\Repositories\Admin\Interfaces\GovernanceLayerRepositoryInterface;

class GovernanceLayerService
{
    public function __construct(
        private GovernanceLayerRepositoryInterface $repo
    ){}

    public function getAll(){
        return $this->repo->all();
    }

    public function create(array $data){
        return $this->repo->create($data);
    }

    public function update(GovernanceLayer $layer, array $data){
        return $this->repo->update($layer, $data);
    }

    public function delete(GovernanceLayer $layer){
        return $this->repo->delete($layer);
    }
}
