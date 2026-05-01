<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\JoiningFees;
use App\Models\UserJoiningFees;
use App\Services\Merchant\MerchantDashboardService;

class MerchantDashboardController extends Controller
{
    protected MerchantDashboardService $merchantDashboardService;

    public function __construct(MerchantDashboardService $merchantDashboardService)
    {
        $this->merchantDashboardService = $merchantDashboardService;
    }

    public function index()
    {
        $userId = auth()->id();

        $hasPaidJoiningFee = UserJoiningFees::where('user_id', $userId)
            ->where('status', 'completed')
            ->exists();

        $joiningFee = JoiningFees::where('role_id', 2)
            ->where('status', 'active')
            ->first();

        $showPopup = !$hasPaidJoiningFee && !empty($joiningFee);
        $joiningFeeAmount = $joiningFee?->amount;
     
        /* Subscription Product Record */
        $products = $this->merchantDashboardService->getProductLimit($userId);
        $customers = $this->merchantDashboardService->customerSubscriptionDetail($userId);

     
        return view('merchant.pages.dashboard', compact(
            'hasPaidJoiningFee',
            'joiningFee',
            'showPopup',
            'joiningFeeAmount',
            'products',
            'customers'
        ));
    }

    public function externalLinks()
    {
        return view('merchant.pages.external-links');
    }

    public function showBarterAssistCashPrice()
    {
        return view('merchant.pages.barter-assist-cash-price');
    }

    public function showBarterAssistPos()
    {
        return view('merchant.pages.barter-assist-pos-demo');
    }
}