<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GovernanceLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GovernanceLayerController extends Controller
{
    /**
     * Show governance layers view.
     */
    public function index()
    {
        $governanceLayers = GovernanceLayer::all();
        return view('admin.governance-layers.index', compact('governanceLayers'));
    }

    /**
     * Show the form for editing the specified governance layer.
     */
    public function edit(GovernanceLayer $governanceLayer)
    {
        return view('admin.governance-layers.edit', compact('governanceLayer'));
    }

    /**
     * Show the form for creating a new governance layer.
     */
    public function create()
    {
        return view('admin.governance-layers.create');
    }

    /**
     * Store a newly created governance layer in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $governanceLayer = GovernanceLayer::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
        ]);

        return redirect()->route('admin.governance-layers')
            ->with('success', 'Governance layer created successfully.');
    }

    /**
     * Update the specified governance layer in storage.
     */
    public function update(Request $request, GovernanceLayer $governanceLayer)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $governanceLayer->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
        ]);

        return redirect()->back()
            ->with('success', 'Governance layer updated successfully.');
    }

    /**
     * Remove the specified governance layer from storage.
     */
    public function destroy(GovernanceLayer $governanceLayer)
    {
        $governanceLayer->delete();

        return redirect()->route('admin.governance-layers')
            ->with('success', 'Governance layer deleted successfully.');
    }
}
