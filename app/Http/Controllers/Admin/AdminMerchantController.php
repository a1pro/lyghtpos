<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\Cyclos\CyclosGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

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

        // Build query - REMOVE the merchants() scope to show ALL users
        $query = User::query();

        // Exclude admin users (role_id = 1) if you don't want to show them
        $query->where('role_id', '!=', 1);

        // Apply role filter if selected
        if ($roleId && $roleId !== 'all') {
            $query->where('role_id', $roleId);
        }

        // Get users with optional caching
        $cacheKey = 'users.list.'.($roleId ?? 'all');

        $users = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn () => $query->with('role')->latest('id')->get()
        );

        // Get all roles for dropdown (excluding admin if needed)
        $roles = Role::where('id', '!=', 1)->orderBy('name')->get();

        return view('admin.pages.merchants.index', compact('users', 'roles', 'roleId'));
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
            // FIXED: Use $user (singular), not $users
            $user = User::with('role')->findOrFail($id);

            // Debug log
            \Log::info('Edit group method called', [
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

            // Pass $user (singular) to the view
            return view('admin.pages.merchants.edit-group', compact('user', 'cyclosGroupInfo', 'availableGroups'));

        } catch (\Exception $e) {
            \Log::error('Error in editGroup', [
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

            \Log::info('Update group method called', [
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

                // Sync updated group info back to local database
                $this->cyclosGroupService->syncUserGroup($user);

                return redirect()->route('admin.merchants.index')
                    ->with('success', "Successfully changed group for {$user->first_name} {$user->last_name} from '{$oldGroupName}' to new group.");
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to change group in Cyclos. Please check the logs for more details.');
            }

        } catch (\Exception $e) {
            \Log::error('Error in updateGroup', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Error updating group: '.$e->getMessage());
        }
    }
}
