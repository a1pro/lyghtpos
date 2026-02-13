<?php

namespace App\Http\Middleware\Merchant;

use App\Models\BrandTeamMember;
use App\Models\Representation;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class MerchantSubscribedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        $bypassRoutes = [
            'login',
            'logout',

            'register',
            'register.final',

            'password.confirm',
            'password.email',
            'password.request',
            'password.update',
            'password.reset',

            'merchant.subscription.plans',
            'merchant.subscription.demo.card',
            'merchant.subscription.demo.card.submit',

            'merchant.representation.submit'
        ];

        if (in_array($request->route()->getName(), $bypassRoutes)) {
            return $next($request);
        }

        if (!Session::has('subscribed')) {
            return redirect()->route('merchant.subscription.plans');
        }
        // if (!$user->hasMethodOnFile()) {
        //     return redirect()->route('merchant.subscription.plans');
        // }

        return $next($request);
    }
}
