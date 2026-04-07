<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\JoiningFeesService;
use App\Http\Requests\Admin\JoiningFeesRequest;
use Illuminate\Http\Request;
use Auth;
use DB;

class JoiningFeesController extends Controller
{
    protected $joiningfees;

    public function __construct(JoiningFeesService $joiningfees)
    {
        $this->joiningfees = $joiningfees;
    }

    public function index()
    {
        try {
            $plans = $this->joiningfees->index();
            
            if (is_array($plans) && isset($plans['success']) && $plans['success'] === false) {
                throw new \Exception($plans['message']);
            }
            
            return view('admin.pages.joiningfees.index', compact('plans'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('admin.pages.joiningfees.create');
    }

    public function store(JoiningFeesRequest $request)
    {
        try {
            DB::beginTransaction();
            $result = $this->joiningfees->storePlan($request->validated());    
            
            if (!$result['success']) {
                throw new \Exception($result['message']);
            }
            
            DB::commit();
            return redirect()->route('admin.joining-fees.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Something went wrong: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        try {
            $result = $this->joiningfees->getPlanById($id);
            
            if (!$result['success']) {
                throw new \Exception($result['message']);
            }
            
            $plan = $result['plan'];
            return view('admin.pages.joiningfees.edit', compact('plan'));
        } catch (\Exception $e) {
            return redirect()->route('admin.joining-fees.index')
                ->with('error', 'Plan not found or error: ' . $e->getMessage());
        }
    }

    public function update(JoiningFeesRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $result = $this->joiningfees->updatePlan($id, $request->validated());
            
            if (!$result['success']) {
                throw new \Exception($result['message']);
            }
            
            DB::commit();
            return redirect()->route('admin.joining-fees.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Something went wrong: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $result = $this->joiningfees->deletePlan($id);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
            
            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete: ' . $e->getMessage()
            ], 500);
        }
    }
}