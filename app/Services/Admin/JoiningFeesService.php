<?php

namespace App\Services\Admin;

use App\Models\JoiningFees;
use Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class JoiningFeesService 
{
    public function index()
    {
        try {
            return JoiningFees::with('role')->get();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to fetch the joining fees plans: ' . $e->getMessage()
            ];
        }
    }

        public function storePlan(array $data): array
    {
        $userId = Auth::id();
        try {
            // Check if entry already exists for this role_id
            $existingPlan = JoiningFees::where('role_id', $data['role_id'])->first();
            
            if ($existingPlan) {
                // Update existing plan
                $existingPlan->update([
                    'amount' => $data['amount'],
                    'status' => $data['status'] ?? 'active',
                    'user_id' => $userId // Track who updated
                ]);
                
                return [
                    'success' => true,
                    'plan' => $existingPlan,
                    'message' => 'Plan Updated Successfully (Role already had a plan)',
                    'action' => 'updated'
                ];
            } else {
                // Create new plan
                $plan = JoiningFees::create([
                    'user_id' => $userId,
                    'role_id' => $data['role_id'],
                    'amount' => $data['amount'],
                    'status' => $data['status'] ?? 'active'
                ]);
                
                return [
                    'success' => true,
                    'plan' => $plan,
                    'message' => 'Plan Added Successfully',
                    'action' => 'created'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to process the plan: ' . $e->getMessage(),
            ];
        }
}

    public function getPlanById($id): array
    {
        try {
            $plan = JoiningFees::with('role')->findOrFail($id);
            return [
                'success' => true,
                'plan' => $plan
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Plan not found'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to fetch plan: ' . $e->getMessage()
            ];
        }
    }

    public function updatePlan($id, array $data): array
    {
        try {
            $plan = JoiningFees::findOrFail($id);
            $plan->update([
                'role_id' => $data['role_id'],
                'amount' => $data['amount'],
                'status' => $data['status'] ?? $plan->status
            ]);

            return [
                'success' => true,
                'plan' => $plan,
                'message' => 'Plan Updated Successfully'
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Plan not found'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to update the plan: ' . $e->getMessage(),
            ];
        }
    }

    public function deletePlan($id): array
    {
        try {
            $plan = JoiningFees::findOrFail($id);
            $plan->delete();

            return [
                'success' => true,
                'message' => 'Plan Deleted Successfully'
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Plan not found'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to delete the plan: ' . $e->getMessage(),
            ];
        }
    }
}