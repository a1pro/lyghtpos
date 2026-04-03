<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\Cyclos\CyclosGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminMerchantController extends Controller
{
    protected $cyclosGroupService;

    public function __construct(CyclosGroupService $cyclosGroupService)
    {
        $this->cyclosGroupService = $cyclosGroupService;
    }

    public function renderMerchants(Request $request)
    {
        $roleId = $request->get('role_id');

        // Build query - show ALL users except admin
        $query = User::query();

        // Exclude admin users (role_id = 1) if you don't want to show them
        $query->where('role_id', '!=', 1);

        // Apply role filter if selected
        if ($roleId && $roleId !== 'all') {
            $query->where('role_id', $roleId);
        }

        // Get users with their roles
        $users = $query->with('role')->latest('id')->get();

        // Get all roles for dropdown (excluding admin if needed)
        $roles = Role::where('id', '!=', 1)->orderBy('name')->get();

        // Get all merchants for assignment dropdown (case-insensitive) - Fixed ordering
        $merchants = User::whereHas('role', function ($query) {
            $query->whereRaw('LOWER(name) = ?', ['merchant']);
        })->where('status', 1)
            ->orderBy('first_name') // Changed from business_name to first_name
            ->get();

        // Pre-fetch Cyclos group names for all users to avoid N+1 queries
        $cyclosGroups = [];
        foreach ($users as $user) {
            if ($user->cyclos_user_id) {
                $cacheKey = 'cyclos_group_'.$user->cyclos_user_id;
                $cyclosGroups[$user->id] = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user) {
                    try {
                        $groupInfo = $this->cyclosGroupService->getUserGroup($user->cyclos_user_id);

                        return $groupInfo['current_group']['name'] ?? 'Unknown';
                    } catch (\Exception $e) {
                        Log::warning('Could not fetch Cyclos group for user '.$user->id, ['error' => $e->getMessage()]);

                        return 'Error';
                    }
                });
            } else {
                $cyclosGroups[$user->id] = 'Not Synced';
            }
        }

        return view('admin.pages.merchants.index', compact('users', 'roles', 'roleId', 'merchants', 'cyclosGroups'));
    }

    public function showChangePasswordForm($id)
    {
        $user = User::findOrFail($id);

        return view('admin.pages.merchants.change-password', compact('user'));
    }

    public function updatePassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($request->password);
        $user->save();

        // Clear all relevant caches
        Cache::forget('users.list.all');
        $roles = Role::all();
        foreach ($roles as $role) {
            Cache::forget('users.list.'.$role->id);
        }

        return redirect()->route('admin.merchants.index')
            ->with('success', 'Password changed successfully for '.$user->first_name.' '.$user->last_name.'!');
    }

    /**
     * Show form to edit user group
     */
    public function editGroup($id)
    {
        try {
            $user = User::with('role')->findOrFail($id);

            Log::info('Edit group method called', [
                'user_id' => $id,
                'user_name' => $user->first_name.' '.$user->last_name,
                'has_cyclos_id' => ! empty($user->cyclos_user_id),
            ]);

            // Get current Cyclos group info
            $cyclosGroupInfo = null;
            $availableGroups = [];

            if ($user->cyclos_user_id) {
                $cyclosGroupInfo = $this->cyclosGroupService->getUserGroup($user->cyclos_user_id);
                $availableGroups = $this->cyclosGroupService->getAllAvailableGroups($user->cyclos_user_id);
            }

            return view('admin.pages.merchants.edit-group', compact('user', 'cyclosGroupInfo', 'availableGroups'));

        } catch (\Exception $e) {
            Log::error('Error in editGroup', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.merchants.index')
                ->with('error', 'Error loading group edit form: '.$e->getMessage());
        }
    }

    /**
     * Update user group in Cyclos
     */
    public function updateGroup(Request $request, $id)
    {
        try {
            $request->validate([
                'group_id' => 'required|string',
                'comment' => 'nullable|string|max:500',
            ]);

            $user = User::findOrFail($id);

            Log::info('Update group method called', [
                'user_id' => $id,
                'group_id' => $request->group_id,
                'comment' => $request->comment,
            ]);

            if (! $user->cyclos_user_id) {
                return redirect()->back()
                    ->with('error', 'This user does not have a Cyclos account yet.');
            }

            // Get current group info for logging
            $oldGroupInfo = $this->cyclosGroupService->getUserGroup($user->cyclos_user_id);
            $oldGroupName = $oldGroupInfo['current_group']['name'] ?? 'Unknown';

            // Change the group in Cyclos
            $success = $this->cyclosGroupService->changeUserGroup(
                $user->cyclos_user_id,
                $request->group_id,
                $request->comment
            );

            if ($success) {
                // Clear caches
                Cache::forget('users.list.all');
                $roles = Role::all();
                foreach ($roles as $role) {
                    Cache::forget('users.list.'.$role->id);
                }

                // Clear Cyclos group cache
                Cache::forget('cyclos_group_'.$user->cyclos_user_id);

                // Sync updated group info back to local database
                $this->cyclosGroupService->syncUserGroup($user);

                return redirect()->route('admin.merchants.index')
                    ->with('success', "Successfully changed group for {$user->first_name} {$user->last_name} from '{$oldGroupName}' to new group.");
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to change group in Cyclos. Please check the logs for more details.');
            }

        } catch (\Exception $e) {
            Log::error('Error in updateGroup', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Error updating group: '.$e->getMessage());
        }
    }

    /**
     * Show form to assign merchant to customer
     */
    public function showAssignMerchantForm($id)
    {
        $customer = User::with('role')->findOrFail($id);

        // Check if user is a customer (case-insensitive)
        if (strtolower($customer->role->name ?? '') !== 'customer') {
            return redirect()->route('admin.merchants.index')
                ->with('error', 'This user is not a customer. Only customers can be assigned to merchants.');
        }

        // Get all active merchants (case-insensitive) - Fixed ordering
        $merchants = User::whereHas('role', function ($query) {
            $query->whereRaw('LOWER(name) = ?', ['merchant']);
        })->where('status', 1)
            ->orderBy('first_name') // Changed from business_name to first_name
            ->get();

        // Get currently assigned merchant
        $assignedMerchant = null;
        if ($customer->merchant_id) {
            $assignedMerchant = User::find($customer->merchant_id);
        }

        return view('admin.pages.merchants.assign-merchant', compact('customer', 'merchants', 'assignedMerchant'));
    }

    /**
     * Assign merchant to customer
     */
    public function assignMerchant(Request $request, $id)
    {
        $request->validate([
            'merchant_id' => 'nullable|exists:users,id',
        ]);

        try {
            $customer = User::with('role')->findOrFail($id);

            // Check if the user is actually a customer (case-insensitive)
            if (strtolower($customer->role->name ?? '') !== 'customer') {
                return redirect()->back()
                    ->with('error', 'This user is not a customer. Only customers can be assigned to merchants.');
            }

            // If merchant_id is provided, verify it's actually a merchant
            if ($request->merchant_id) {
                $merchant = User::find($request->merchant_id);
                if (strtolower($merchant->role->name ?? '') !== 'merchant') {
                    return redirect()->back()
                        ->with('error', 'Selected user is not a merchant.');
                }
            }

            $oldMerchantId = $customer->merchant_id;
            $customer->merchant_id = $request->merchant_id;
            $customer->save();

            // Clear caches
            Cache::forget('users.list.all');
            $roles = Role::all();
            foreach ($roles as $role) {
                Cache::forget('users.list.'.$role->id);
            }

            $merchantName = 'N/A';
            if ($request->merchant_id && isset($merchant)) {
                $merchantName = $merchant->business_name ?? $merchant->first_name.' '.$merchant->last_name;
            }

            $message = $request->merchant_id
                ? "Successfully assigned customer {$customer->first_name} {$customer->last_name} to merchant {$merchantName}."
                : "Successfully removed merchant assignment for customer {$customer->first_name} {$customer->last_name}.";

            Log::info('Merchant assignment updated', [
                'customer_id' => $id,
                'customer_name' => $customer->first_name.' '.$customer->last_name,
                'old_merchant_id' => $oldMerchantId,
                'new_merchant_id' => $request->merchant_id,
                'action_by' => auth()->id(),
            ]);

            return redirect()->route('admin.merchants.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error assigning merchant to customer', [
                'customer_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Error assigning merchant: '.$e->getMessage());
        }
    }
}
