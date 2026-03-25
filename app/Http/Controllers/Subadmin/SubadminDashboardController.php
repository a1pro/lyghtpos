<?php

namespace App\Http\Controllers\Subadmin;

use App\Http\Controllers\Controller;
use App\Models\Representation;
use App\Models\User;
use App\Services\Cyclos\CyclosUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class SubadminDashboardController extends Controller
{
    protected CyclosUserService $cyclosUserService;

    public function __construct(CyclosUserService $cyclosUserService)
    {
        $this->cyclosUserService = $cyclosUserService;
    }

    public function index()
    {
        return view('subadmin.pages.dashboard');
    }

    public function renderMerchants()
    {
        $merchants = Cache::remember(
            'merchants.list',
            now()->addMinutes(10),
            fn() => User::merchants()->latest('id')->get()
        );

        return view('subadmin.pages.merchants.index', compact('merchants'));
    }

    public function showChangePasswordForm($id)
    {
        $merchant = User::findOrFail($id);
        return view('subadmin.pages.merchants.change-password', compact('merchant'));
    }

    // Add this method to handle password update
    public function updatePassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $merchant = User::findOrFail($id);
        $merchant->password = Hash::make($request->password);
        $merchant->save();

        // Clear cache after password change
        Cache::forget('merchants.list');

        return redirect()->route('subadmin.merchants')
            ->with('success', 'Password changed successfully!');
    }

    public function showChangeCyclosPasswordForm($id)
    {
        $merchant = User::findOrFail($id);
        $merchantCyclosId = $merchant->cyclos_user_id;

        if (!$merchantCyclosId) {
            return redirect()->route('subadmin.merchants')
                ->with('error', 'Cyclos ID not found for password change!');
        }

        return view('subadmin.pages.merchants.change-cyclos-password', compact('merchant'));
    }

    public function updateCyclosPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $merchant = User::findOrFail($id);
        $merchantCyclosId = $merchant->cyclos_user_id;

        $response = $this->cyclosUserService->updateUserPassword($merchantCyclosId, $request->password);

        if (!$response) {
            return redirect()->route('subadmin.merchants')
                ->with('error', 'Cyclos Password change failed!');
        }

        $merchant->update([
            'cyclos_user_password' => $request->password
        ]);

        return redirect()->route('subadmin.merchants')
            ->with('success', 'Cyclos Password changed successfully!');
    }
}
