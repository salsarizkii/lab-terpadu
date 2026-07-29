<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next, ...$params): Response
    {
        $token = $request->bearerToken();
        if (!$token) {
            $token = $request->query('token');
        }
        if (!$token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $user = User::where('api_token', $token)->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        // Check if user is verified
        if (!$user->is_verified) {
            return response()->json(['message' => 'Akun Anda belum diverifikasi oleh superadmin'], 403);
        }

        // Superadmin bypasses all checks
        if ($user->role === 'superadmin') {
            Auth::setUser($user);
            return $next($request);
        }

        if (!empty($params)) {
            // Split params: roles (no underscore) and permissions (with underscore)
            $allowedRoles = array_filter($params, fn($p) => !str_contains($p, '_'));
            $requiredPermissions = array_filter($params, fn($p) => str_contains($p, '_'));

            $roleMatch = !empty($allowedRoles) && in_array($user->role, $allowedRoles);

            // If role doesn't match, check permissions as fallback
            if (!$roleMatch && !empty($requiredPermissions)) {
                if (!$user->relationLoaded('roleModel.permissions')) {
                    $user->load('roleModel.permissions');
                }
                $userPermissions = $user->roleModel?->permissions?->pluck('name')->toArray() ?? [];
                $hasPermission = !empty(array_intersect($requiredPermissions, $userPermissions));

                if ($hasPermission) {
                    Auth::setUser($user);
                    return $next($request);
                }
            }

            if (!$roleMatch) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        Auth::setUser($user);
        return $next($request);
    }
}

