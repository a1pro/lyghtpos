<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PlanController extends Controller
{
    /**
     * Display the plans page
     */
    public function index()
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        Session::forget('subscribed');

        return view('merchant.pages.subscription.plans', compact('plans'));
    }

    public function showCardDetails()
    {
        return view('merchant.pages.subscription.demo-card-details');
    }

    public function submitPayment()
    {
        Session::put('subscribed', true);
        return redirect()->route('merchant.dashboard');
    }
}
