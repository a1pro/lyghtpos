<?php

namespace App\Http\Controllers\Subadmin;

use App\Http\Controllers\Controller;
use App\Models\Representation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class SubadminDashboardController extends Controller
{
    public function index()
    {
        return view('subadmin.pages.dashboard');
    }

    public function renderMerchants()
    {
        $merchants = Cache::remember(
            'merchants.list',
            now()->addMinutes(10),
            fn () => User::merchants()->latest('id')->get()
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

}
