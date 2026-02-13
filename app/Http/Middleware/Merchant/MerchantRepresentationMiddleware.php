<?php

namespace App\Http\Middleware\Merchant;

use App\Models\BrandTeamMember;
use App\Models\Representation;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MerchantRepresentationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = Auth::id();

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
            'merchant.representation.form',

            'merchant.subscription.demo.card',
            'merchant.subscription.demo.card.submit',

            'merchant.representation.submit'
        ];

        if (in_array($request->route()->getName(), $bypassRoutes)) {
            return $next($request);
        }

        $representation = Representation::where('user_id', $userId)->where('status', 'approved')->first();

        if (!$representation) {
            return redirect()->route('merchant.representation.form');
        }

        return $next($request);
    }
}
