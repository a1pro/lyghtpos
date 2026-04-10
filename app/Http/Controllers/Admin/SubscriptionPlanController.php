<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionPlanRequest;
use App\Services\Admin\SubscriptionPlanService;
use DB;

class SubscriptionPlanController extends Controller
{
    protected $subscriptionPlanService;

    public function __construct(SubscriptionPlanService $subscriptionPlanService)
    {
        $this->subscriptionPlanService = $subscriptionPlanService;
    }

    public function index()
    {
        try {
            $plans = $this->subscriptionPlanService->index();

            if (is_array($plans) && isset($plans['success']) && $plans['success'] === false) {
                throw new \Exception($plans['message']);
            }

            return view('admin.pages.subscription-plan.index', compact('plans'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('admin.pages.subscription-plan.create');
    }

    public function store(SubscriptionPlanRequest $request)
    {
        try {
            DB::beginTransaction();
            $result = $this->subscriptionPlanService->storePlan($request->validated());

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            DB::commit();
            return redirect()->route('admin.subscription-plans.index')
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
            $result = $this->subscriptionPlanService->getPlanById($id);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            $plan = $result['plan'];

            return view('admin.pages.subscription-plan.edit', compact('plan'));
        } catch (\Exception $e) {
            return redirect()->route('admin.subscription-plans.index')
                ->with('error', 'Plan not found or error: ' . $e->getMessage());
        }
    }

    public function update(SubscriptionPlanRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $result = $this->subscriptionPlanService->updatePlan($id, $request->validated());

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            DB::commit();
            return redirect()->route('admin.subscription-plans.index')
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
            $result = $this->subscriptionPlanService->deletePlan($id);

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