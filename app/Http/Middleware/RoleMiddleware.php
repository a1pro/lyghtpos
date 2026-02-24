<?php

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

        // Get user's role name and id
        $userRole = $user->role;
        $userRoleName = $userRole->name;
        $userRoleId = $userRole->id;

        // Check if user's role is in the allowed roles
        if (!in_array($userRoleName, $roles)) {
            
            // Role ID 2 (merchant) trying to access role ID 1 (admin) routes
            if ($userRoleId == 2) {
                return redirect()->route('merchant.dashboard')
                    ->with('error', 'Unauthorized access. You do not have permission to view this page.');
            }
            
            // Role ID 1 (admin) trying to access role ID 2 (merchant) routes
            if ($userRoleId == 1) {
                return redirect()->route('home')
                    ->with('error', 'Unauthorized access. You do not have permission to view this page.');
            }
            
            // For any other role combinations
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}