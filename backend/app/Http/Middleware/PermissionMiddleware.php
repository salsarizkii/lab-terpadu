<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Superadmin bypass semua permission
        if ($user->role === 'superadmin') {
            return $next($request);
        }

        // Load role + permissions jika belum di-eager-load
        if (!$user->relationLoaded('roleModel.permissions')) {
            $user->load('roleModel.permissions');
        }

        $userPermissions = $user->roleModel?->permissions?->pluck('name')->toArray() ?? [];

        // Cek semua permission harus dimiliki (AND logic)
        foreach ($permissions as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return response()->json([
                    'message' => 'Anda tidak memiliki hak akses: ' . $permission,
                ], 403);
            }
        }

        return $next($request);
    }
}
