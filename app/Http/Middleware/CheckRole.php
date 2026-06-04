<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please log in.',
                'timestamp' => now()->toISOString(),
                'code' => 401,
                'error' => [
                    'code' => 'AUTHENTICATION_REQUIRED',
                    'details' => 'Valid authentication token is required'
                ]
            ], 401);
        }

        // Check if user has any of the required roles
        $userRole = $user->getPrimaryRole();

        foreach ($roles as $role) {
            if ($userRole === $role) {
                return $next($request);
            }
        }

        // Map role slugs to human-readable names
        $roleNames = $this->getRoleDisplayNames($roles);

        return response()->json([
            'success' => false,
            'message' => 'Access denied. You don\'t have the required role access.',
            'timestamp' => now()->toISOString(),
            'code' => 403,
            'error' => [
                'code' => 'ROLE_ACCESS_DENIED',
                'details' => 'Required role(s): ' . implode(', ', $roleNames),
                'required_roles' => $roles,
                'required_roles_display' => $roleNames,
                'user_role' => $userRole,
                'user_role_display' => $this->getRoleDisplayNames([$userRole])[0] ?? $userRole
            ]
        ], 403);
    }

    /** 
     * Convert role slugs to human-readable names
     */
    private function getRoleDisplayNames(array $roleSlugs): array
    {
        $roleMapping = [
            'employee' => 'Employee',
            'manager' => 'Manager',
            'hr_manager' => 'HR Manager',
            'company_owner' => 'Company Owner',
            'interviewer' => 'Interviewer',
            'candidate' => 'Candidate',
            'platform_super_admin' => 'Platform Administrator',
        ];

        return array_map(function ($slug) use ($roleMapping) {
            return $roleMapping[$slug] ?? ucfirst(str_replace('_', ' ', $slug));
        }, $roleSlugs);
    }
}
