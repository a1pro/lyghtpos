<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     */
    protected string $redirectTo = '/admin';

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        switch ($user->role_id) {
            case 1: // Admin
                return redirect()->route('home');
            case 2: // Merchant
                return redirect()->route('merchant.dashboard');
            case 5: // Subadmin
                return redirect()->route('subadmin.dashboard');
            case 6: // Trade Member
                return redirect()->route('trade-member.dashboard');
            case 7: // Organization
                return redirect()->route('organization-admin.dashboard');
            case 8: // Favor Member
                return redirect()->route('favor-member.dashboard');
            default:
                return redirect()->route('merchant.dashboard'); // fallback
        }

        // Fallback
        return redirect('/home');
    }

    /**
     * Get the post register / login redirect path.
     */
    public function redirectPath(): string
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return $this->redirectTo;
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}
