<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'role' => 'nullable|string|in:user,admin,analyst',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['api_token'] = Str::random(60);
        $validated['role'] = $validated['role'] ?? 'user';

        $user = User::create($validated);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $user->api_token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user->api_token = Str::random(60);
        $user->save();

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $user->api_token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->api_token = null;
        $request->user()->save();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
