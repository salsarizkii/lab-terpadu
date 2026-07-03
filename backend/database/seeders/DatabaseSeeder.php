<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PermissionsSeeder::class);
        $this->call(FormTemplateSeeder::class);

        // Seed only superadmin role (admin/analyst/user must be created via UI)
        Role::firstOrCreate(
            ['name' => 'superadmin'],
            [
                'description' => 'Super Administrator dengan akses penuh ke seluruh sistem',
                'is_system' => true,
            ]
        );

        // Seed user role for registration (auto-verified, no superadmin approval needed)
        $userRole = Role::firstOrCreate(
            ['name' => 'user'],
            [
                'description' => 'Pengguna biasa untuk pengajuan pengujian sampel',
                'is_system' => false,
                'requires_verification' => false,
            ]
        );

        $userPermissions = Permission::whereIn('name', [
            'berita_acara_create',
            'berita_acara_riwayat_personal',
            'berita_acara_view',
            'pengujian_riwayat_personal',
            'pengujian_input',
        ])->get();
        $userRole->permissions()->syncWithoutDetaching($userPermissions->pluck('id'));

        // Seed only 1 superadmin account
        User::firstOrCreate(
            ['email' => 'superadmin@lab.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'role' => 'superadmin',
                'is_verified' => true,
            ]
        );
    }
}
