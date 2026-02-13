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
}
