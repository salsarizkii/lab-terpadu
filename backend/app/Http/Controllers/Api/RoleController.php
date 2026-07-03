<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->with('permissions')->get();

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name|regex:/^[a-z_]+$/',
            'description' => 'nullable|string|max:500',
            'requires_verification' => 'nullable|boolean',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_system' => false,
            'requires_verification' => $validated['requires_verification'] ?? false,
        ]);

        if (!empty($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        $role->load('permissions');

        return response()->json([
            'message' => 'Role berhasil dibuat',
            'data' => $role,
        ], 201);
    }

    public function show($id)
    {
        $role = Role::withCount('users')->with('permissions')->findOrFail($id);

        return response()->json($role);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $rules = [
            'description' => 'nullable|string|max:500',
            'requires_verification' => 'nullable|boolean',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ];

        if (!$role->is_system) {
            $rules['name'] = 'sometimes|string|max:50|unique:roles,name,' . $role->id . '|regex:/^[a-z_]+$/';
        }

        $validated = $request->validate($rules);

        if ($role->is_system && isset($validated['name'])) {
            unset($validated['name']);
        }

        // Simpan permission_ids lalu hapus dari array update
        $hasPermissionIds = array_key_exists('permission_ids', $validated);
        $permissionIds = $validated['permission_ids'] ?? [];
        unset($validated['permission_ids']);

        $role->update($validated);

        // Hanya sync jika permission_ids dikirim dalam request
        if ($hasPermissionIds) {
            $role->permissions()->sync($permissionIds);
        }

        $role->load('permissions');

        return response()->json([
            'message' => 'Role berhasil diperbarui',
            'data' => $role,
        ]);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if ($role->is_system) {
            return response()->json([
                'message' => 'Role sistem tidak dapat dihapus',
            ], 403);
        }

        $userCount = \App\Models\User::where('role', $role->name)->count();
        if ($userCount > 0) {
            return response()->json([
                'message' => 'Role tidak dapat dihapus karena masih digunakan oleh ' . $userCount . ' pengguna',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role berhasil dihapus',
        ]);
    }

    public function listPermissions()
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        return response()->json($permissions);
    }
}
