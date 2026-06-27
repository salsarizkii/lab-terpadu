<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $token = $request->bearerToken();
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

        // Superadmin bypasses all role checks
        if (!empty($roles) && $user->role !== 'superadmin' && !in_array($user->role, $roles)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        Auth::setUser($user);
        return $next($request);
    }
}

