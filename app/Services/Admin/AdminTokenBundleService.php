<?php

namespace App\Services\Admin;

use App\Models\TokenBundle;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminTokenBundleService
{
    public function index()
    {
        try {
            return TokenBundle::orderBy('created_at', 'desc')->get();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to fetch the token bundles: ' . $e->getMessage()
            ];
        }
    }

    public function storeTokenBundle(array $data): array
    {
        try {
            $bundle = TokenBundle::create([
                'tokens'       => $data['tokens'],
                'base_amount' => $data['base_amount'],
                'system_fee' => $data['system_fee'],
                'discounted' => $data['discounted'],
                'status' => $data['status'],
            ]);

            return [
                'success' => true,
                'bundle' => $bundle,
                'message' => 'Token Bundle Created Successfully',
                'action' => 'created'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to process the bundle: ' . $e->getMessage(),
            ];
        }
    }

    public function getTokenBundleById($id): array
    {
        try {
            $bundle = TokenBundle::findOrFail($id);

            return [
                'success' => true,
                'bundle' => $bundle
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Bundle not found'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to fetch bundle: ' . $e->getMessage()
            ];
        }
    }

    public function updateBundle($id, array $data): array
    {
        try {
            $bundle = TokenBundle::findOrFail($id);

            $bundle->update([
                'tokens'       => $data['tokens'],
                'base_amount' => $data['base_amount'],
                'system_fee' => $data['system_fee'],
                'discounted' => $data['discounted'] ?? $bundle->discounted,
                'status' => $data['status'] ?? $bundle->status,
            ]);

            return [
                'success' => true,
                'bundle' => $bundle,
                'message' => 'Bundle Updated Successfully'
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Bundle not found'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to update the bundle: ' . $e->getMessage(),
            ];
        }
    }

    public function deleteTokenBundle($id): array
    {
        try {
            $bundle = TokenBundle::findOrFail($id);
            $bundle->delete();

            return [
                'success' => true,
                'message' => 'Bundle Deleted Successfully'
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Bundle not found'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to delete the bundle: ' . $e->getMessage(),
            ];
        }
    }
}
