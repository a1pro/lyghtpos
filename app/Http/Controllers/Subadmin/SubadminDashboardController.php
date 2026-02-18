<?php

namespace App\Http\Controllers\Subadmin;

use App\Http\Controllers\Controller;
use App\Models\Representation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

}
