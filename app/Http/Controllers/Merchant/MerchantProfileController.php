<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\Merchant\MerchantProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantProfileController extends Controller
{
    protected $merchantProfileService;

    public function __construct(MerchantProfileService $merchantProfileService)
    {
        $this->merchantProfileService = $merchantProfileService;
    }

    /**
     * Display merchant profile page
     */
    public function index()
    {
        $user = Auth::user();
        $profileData = $this->merchantProfileService->getProfileData($user);

        return view('merchant.pages.profile', $profileData);
    }

    /**
     * Update profile information
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $user = Auth::user();
            $result = $this->merchantProfileService->updateProfile($user, $request->all());

            if ($result['success']) {
                return redirect()->route('merchant.profile.index')->with('success', $result['message']);
            }

            return redirect()->route('merchant.profile.index')->with('error', $result['message']);
        } catch (\Exception $e) {
            return redirect()->route('merchant.profile.index')->with('error', 'Failed to update profile');
        }
    }
}
