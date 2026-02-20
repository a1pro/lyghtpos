<?php

namespace App\Http\Controllers\OrganizationAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrganizationAdminDashboardController extends Controller
{
    public function index()
    {
        return view('organization-admin.pages.dashboard');
    }
}
