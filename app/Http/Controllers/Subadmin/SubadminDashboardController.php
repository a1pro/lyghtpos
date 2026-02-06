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

    public function renderUsers()
    {
        $users = User::where('role_id', '2')->get();
        
        return view('subadmin.pages.users.index', compact('users'));
    }

    public function renderUserRepresentation($userId)
    {
        $representation = Representation::where('user_id', $userId)->with('user')->first();

        return view('subadmin.pages.users.representation-view', compact('representation'));
    }
}
