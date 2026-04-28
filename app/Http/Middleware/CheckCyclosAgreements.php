<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Cyclos\CyclosUserService;

class CheckCyclosAgreements
{
    protected $cyclosUserService;

    public function __construct(CyclosUserService $cyclosUserService)
    {
        $this->cyclosUserService = $cyclosUserService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

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

        // Skip if user is not logged in
        if (!$user) {
            return $next($request);
        }

        // Check if user has already agreed to terms in this session
        if ($request->session()->get('cyclos_agreements_accepted', false)) {
            return $next($request);
        }

        // Skip if user is admin or subadmin (role_id 1 or 5)
        if (in_array($user->role_id, [1, 5])) {
            return $next($request);
        }

        // Get user's Cyclos credentials
        $credentials = $this->cyclosUserService->getUserCredentials($user);

        if (!$credentials) {
            return $next($request);
        }

        // Fetch pending agreements
        $agreements = $this->cyclosUserService->getUserAgreements(
            $credentials['username'],
            $credentials['password']
        );

        // Store agreements in session for later use
        if ($agreements && count($agreements) > 0) {
            $request->session()->put('pending_agreements', $agreements);
            $request->session()->put('cyclos_credentials', $credentials);

            // Redirect to agreements page
            return redirect()->route('merchant.agreements.index');
        }

        // No pending agreements, mark as accepted
        $request->session()->put('cyclos_agreements_accepted', true);

        return $next($request);
    }
}
