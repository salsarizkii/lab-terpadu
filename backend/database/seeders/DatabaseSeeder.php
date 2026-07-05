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

    public function run(): void
    {
        $this->call(PermissionsSeeder::class);

        $allPermissions = Permission::all();

        // Superadmin — full access
        $superadminRole = Role::firstOrCreate(
            ['name' => 'superadmin'],
            [
                'description' => 'Super Administrator dengan akses penuh ke seluruh sistem',
                'is_system' => true,
            ]
        );
        $superadminRole->permissions()->sync($allPermissions->pluck('id'));

        // Admin
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'description' => 'Administrator untuk mengelola data dan pengguna',
                'is_system' => false,
                'requires_verification' => true,
            ]
        );
        $adminRole->permissions()->sync($allPermissions->pluck('id'));

        // Analyst
        $analystRole = Role::firstOrCreate(
            ['name' => 'analyst'],
            [
                'description' => 'Analis laboratorium untuk pengelolaan sampel dan rekap analisis',
                'is_system' => false,
                'requires_verification' => true,
            ]
        );
        $analystPermissions = Permission::whereIn('name', [
            'dashboard',
            'berita_acara_view',
            'berita_acara_riwayat_semua',
            'sample_view',
            'sample_input',
            'sample_rekap',
            'sample_status',
            'sample_riwayat_personal',
            'certificate_view',
            'certificate_download',
        ])->get();
        $analystRole->permissions()->sync($analystPermissions->pluck('id'));

        // User
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
            'berita_acara_view',
            'berita_acara_riwayat_personal',
            'sample_riwayat_personal',
            'sample_rekap',
            'payment_view',
            'payment_upload',
            'payment_discount',
            'certificate_view',
            'certificate_download',
        ])->get();
        $userRole->permissions()->sync($userPermissions->pluck('id'));

        // Superadmin account
        User::firstOrCreate(
            ['email' => 'superadmin@lab.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'role' => 'superadmin',
                'is_verified' => true,
            ]
        );

        // Form templates (needs superadmin user to exist)
        $this->call(FormTemplateSeeder::class);
    }
}
