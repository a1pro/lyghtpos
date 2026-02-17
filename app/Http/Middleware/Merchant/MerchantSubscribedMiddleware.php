<?php

namespace App\Http\Middleware\Merchant;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MerchantSubscribedMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

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

        $currentRoute = $request->route()->getName();

        // ✅ If user IS subscribed
        if ($user->subscribed === 'yes') {

            // If subscribed user tries to access plan page → redirect to dashboard
            if ($currentRoute === 'merchant.subscription.plans') {
                return redirect()->route('merchant.dashboard');
            }

            return $next($request);
        }

        // ✅ If user is NOT subscribed
        if (!in_array($currentRoute, $bypassRoutes)) {
            return redirect()->route('merchant.subscription.plans');
        }

        return $next($request);
    }
}
