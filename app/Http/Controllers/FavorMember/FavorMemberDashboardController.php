<?php

namespace App\Http\Controllers\FavorMember;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FavorMemberDashboardController extends Controller
{
    public function index()
    {
        return view('favor-member.pages.dashboard');
    }
}
