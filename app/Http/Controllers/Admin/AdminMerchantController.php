<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Representation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class AdminMerchantController extends Controller
{
   

    public function renderMerchants()
    {
        $merchants = Cache::remember(
            'merchants.list',
            now()->addMinutes(10),
            fn () => User::merchants()->latest('id')->get()
        );
    
        return view('admin.pages.merchants.index', compact('merchants'));
    }

    public function showChangePasswordForm($id)
{
    $merchant = User::findOrFail($id);
    return view('admin.pages.merchants.change-password', compact('merchant'));
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

    return redirect()->route('admin.merchants.index')
        ->with('success', 'Password changed successfully!');
}
}
