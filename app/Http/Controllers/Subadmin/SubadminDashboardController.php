<?php

namespace App\Http\Controllers\Subadmin;

use App\Http\Controllers\Controller;
use App\Models\Representation;
use App\Models\User;
use Illuminate\Http\Request;

class SubadminDashboardController extends Controller
{
    public function index()
    {
        return view('subadmin.pages.dashboard');
    }

    public function renderMerchants()
    {
        $merchants = User::where('role_id', '2')->get();
        
        return view('subadmin.pages.merchants.index', compact('merchants'));
    }
}
