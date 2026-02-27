<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GovernanceLayer;
use App\Services\Admin\GovernanceLayerService;
use App\Http\Requests\Admin\StoreGovernanceLayerRequest;
use App\Http\Requests\Admin\UpdateGovernanceLayerRequest;

class GovernanceLayerController extends Controller
{
    public function __construct(
        private GovernanceLayerService $service
    ) {}

    public function index()
    {
        $governanceLayers = $this->service->getAll();

        return view('admin.governance-layers.index', compact('governanceLayers'));
    }

    public function create()
    {
        return view('admin.governance-layers.create');
    }

    public function store(StoreGovernanceLayerRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->route('admin.governance-layers')
            ->with('success', 'Created successfully');
    }

    public function edit(GovernanceLayer $governanceLayer)
    {
        return view('admin.governance-layers.edit', compact('governanceLayer'));
    }

    public function update(UpdateGovernanceLayerRequest $request, GovernanceLayer $governanceLayer)
    {
        $this->service->update($governanceLayer, $request->validated());

        return back()->with('success', 'Updated successfully');
    }

    public function destroy(GovernanceLayer $governanceLayer)
    {
        $this->service->delete($governanceLayer);

        return redirect()->route('admin.governance-layers')
            ->with('success', 'Deleted successfully');
    }
}