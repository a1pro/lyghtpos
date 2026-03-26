<?php
// app/Http/Middleware/RoleMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = auth()->user();

        // If user is not authenticated
        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        // Get user's role - handle role_id
        $userRoleName = null;
        
        // Check if role relationship exists and has name
        if (method_exists($user, 'role') && $user->role) {
            $userRoleName = $user->role->name;
        }
        // Check if role_id exists directly
        elseif (isset($user->role_id)) {
            // Map role_id to role name
            $roleMap = [
                1 => 'admin',
                2 => 'merchant',
                3 => 'subadmin',
                6 => 'member',
                7 => 'organization',
                8 => 'favor_member',
            ];
            $userRoleName = $roleMap[$user->role_id] ?? null;
        }
        // Check if role is a direct string field
        elseif (isset($user->role) && is_string($user->role)) {
            $userRoleName = $user->role;
        }

        // If user has no role assigned
        if (!$userRoleName) {
            abort(403, 'User role not assigned.');
        }

        // Check if user's role is in the allowed roles
        if (!in_array($userRoleName, $roles)) {
            // Redirect based on role
            if ($userRoleName === 'merchant') {
                return redirect()->route('merchant.dashboard')
                    ->with('error', 'Unauthorized access. You do not have permission to view this page.');
            }
            
            if ($userRoleName === 'admin') {
                return redirect()->route('home')
                    ->with('error', 'Unauthorized access. You do not have permission to view this page.');
            }
            
            // For any other role combinations
            abort(403, 'Unauthorized. This area is restricted to: ' . implode(', ', $roles));
        }

        return $next($request);
    }
}