<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MerchantDashboardController extends Controller
{
    public function index()
    {
        return view('merchant.pages.dashboard');
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
