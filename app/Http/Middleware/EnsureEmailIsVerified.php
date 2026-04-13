<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $role  Optional role parameter to check only for specific roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role = null)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // If role parameter is provided
        if ($role && !$this->userHasRole($user, $role)) {
            return $next($request);
        }

        if (is_null($user->email_verified_at)) {
            $excludedRoutes = [
                '*.verify.email',
                '*.verify.send-otp',
                '*.verify.verify-otp',
                '*.verify.status',
                
                // Auth routes
                'logout',
                'login',
            ];

            $currentRouteName = $request->route()->getName();

            // Check if current route should be excluded
            $isExcluded = false;
            foreach ($excludedRoutes as $excluded) {
                if (str_contains($excluded, '*')) {
                    $pattern = str_replace('*', '.*', $excluded);
                    if (preg_match('/^' . $pattern . '$/', $currentRouteName)) {
                        $isExcluded = true;
                        break;
                    }
                } elseif ($currentRouteName === $excluded) {
                    $isExcluded = true;
                    break;
                }
            }

            // If not excluded, redirect to verification page
            if (!$isExcluded) {
                // Store intended URL
                session()->put('url.intended', $request->url());

                // Determine verification route prefix based on user role
                $verificationRoute = $this->getVerificationRoute($user);

                return redirect()->route($verificationRoute . '.verify.email')
                    ->with('warning', 'Please verify your email address to continue.');
            }
        }

        return $next($request);
    }

    /**
     * Check if user has a specific role
     */
    private function userHasRole($user, $role)
    {
        // Check if role relationship exists
        if (method_exists($user, 'role') && $user->role) {
            return $user->role->name === $role;
        }

        // Check if role_id exists
        if (isset($user->role_id)) {
            $roleMap = [
                1 => 'admin',
                2 => 'merchant',
                3 => 'subadmin',
                6 => 'member',
                7 => 'organization',
                8 => 'favor_member',
            ];
            $userRole = $roleMap[$user->role_id] ?? null;
            return $userRole === $role;
        }

        // Check if role is a direct string field
        if (isset($user->role) && is_string($user->role)) {
            return $user->role === $role;
        }

        return false;
    }

    /**
     * Get verification route prefix based on user role
     */
    private function getVerificationRoute($user)
    {
        $rolePrefixMap = [
            'merchant' => 'merchant',
            'trade_member' => 'trade-member',
            'organization' => 'organization-admin',
            'admin' => 'admin',
        ];

        $userRole = null;

        // Get user role
        if (method_exists($user, 'role') && $user->role) {
            $userRole = $user->role->name;
        } elseif (isset($user->role_id)) {
            $roleMap = [
                1 => 'admin',
                2 => 'merchant',
                3 => 'subadmin',
                6 => 'member',
                7 => 'organization',
                8 => 'favor_member',
            ];
            $userRole = $roleMap[$user->role_id] ?? null;
        } elseif (isset($user->role) && is_string($user->role)) {
            $userRole = $user->role;
        }

        return $rolePrefixMap[$userRole] ?? 'merchant';
    }
}