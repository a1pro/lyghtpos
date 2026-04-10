<?php

namespace App\Services\Admin;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Str;

class SubscriptionPlanService
{
    public function index()
    {
        try {
            return SubscriptionPlan::orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to fetch subscription plans: ' . $e->getMessage()
            ];
        }
    }

    public function storePlan(array $data): array
    {
        try {
            // Generate slug from name
            $data['slug'] = Str::slug($data['name']);

            // Handle features as JSON
            if (isset($data['features']) && is_array($data['features'])) {
                $data['features'] = array_filter($data['features']); // Remove empty values
            }

            $plan = SubscriptionPlan::create($data);

            return [
                'success' => true,
                'plan' => $plan,
                'message' => 'Subscription Plan Created Successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to create plan: ' . $e->getMessage(),
            ];
        }
    }

    public function getPlanById($id): array
    {
        try {
            $plan = SubscriptionPlan::findOrFail($id);

            return [
                'success' => true,
                'plan' => $plan
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Subscription plan not found'
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
            $plan = SubscriptionPlan::findOrFail($id);

            // Update slug if name changed
            if (isset($data['name']) && $data['name'] !== $plan->name) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Handle features as JSON
            if (isset($data['features']) && is_array($data['features'])) {
                $data['features'] = array_filter($data['features']);
            }

            $plan->update($data);

            return [
                'success' => true,
                'plan' => $plan,
                'message' => 'Subscription Plan Updated Successfully'
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Subscription plan not found'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to update plan: ' . $e->getMessage(),
            ];
        }
    }

    public function deletePlan($id): array
    {
        try {
            $plan = SubscriptionPlan::findOrFail($id);
            $plan->delete();

            return [
                'success' => true,
                'message' => 'Subscription Plan Deleted Successfully'
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Subscription plan not found'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to delete plan: ' . $e->getMessage(),
            ];
        }
    }
}