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
        if (!empty($roles) && !in_array($user->role, $roles)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        Auth::setUser($user);
        return $next($request);
    }
}
