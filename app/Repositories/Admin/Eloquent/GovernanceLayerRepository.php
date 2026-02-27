<?php
namespace App\Repositories\Admin\Eloquent;
use App\Models\GovernanceLayer;
use App\Repositories\Admin\Interfaces\GovernanceLayerRepositoryInterface;

class GovernanceLayerRepository implements GovernanceLayerRepositoryInterface
{
    public function all()
    {
        return GovernanceLayer::latest()->get();
    }
    public function create(array $data)
    {
        return GovernanceLayer::create($data);
    }
    
    public function update(GovernanceLayer $layer, array $data)
    {
        $layer->update($data);
        return $layer;
    }


    public function delete(GovernanceLayer $layer)
    {
        return $layer->delete();
    }
}