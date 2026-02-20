<?php

namespace App\Http\Controllers\TradeMember;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TradeMemberDashboardController extends Controller
{
    public function index()
    {
        return view('trade-member.pages.dashboard');
    }
}
