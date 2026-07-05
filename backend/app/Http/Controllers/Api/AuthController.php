<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * List roles available for registration (excludes superadmin).
     */
    public function listPublicRoles()
    {
        $roles = Role::where('name', '!=', 'superadmin')
            ->select('id', 'name', 'description', 'requires_verification')
            ->get();

        return response()->json($roles);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'role' => 'nullable|string',
        ]);

        $validated['role'] = $validated['role'] ?: 'user';

        // Prevent registering as superadmin
        if ($validated['role'] === 'superadmin') {
            return response()->json(['message' => 'Role tidak valid untuk registrasi'], 422);
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['api_token'] = Str::random(60);

        // Check if the selected role requires superadmin verification
        $role = Role::where('name', $validated['role'])->first();
        $validated['is_verified'] = $role ? !$role->requires_verification : true;

        $user = User::create($validated);

        $message = $validated['is_verified']
            ? 'Registrasi berhasil. Silakan login.'
            : 'Registrasi berhasil. Akun Anda perlu diverifikasi oleh superadmin sebelum dapat login.';

        return response()->json([
            'message' => $message,
            'needs_verification' => !$validated['is_verified'],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Akun dengan email tersebut belum terdaftar'], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Password yang Anda masukkan salah'], 401);
        }

        // Check if user is verified
        if (!$user->is_verified) {
            return response()->json([
                'message' => 'Akun Anda belum diverifikasi oleh superadmin. Silakan hubungi administrator.',
            ], 403);
        }

        $user->api_token = Str::random(60);
        $user->save();

        $user->load('roleModel.permissions');

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
        $user = $request->user();
        $user->load('roleModel.permissions');
        return response()->json($user);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($validated);
        $user->load('roleModel.permissions');

        return response()->json([
            'message' => 'Profile berhasil diperbarui',
            'user' => $user,
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Password lama tidak sesuai'], 422);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json(['message' => 'Password berhasil diubah']);
    }
}

