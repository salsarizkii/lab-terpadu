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

        // Admin — full access
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
                'description' => 'Analis laboratorium untuk pengelolaan hasil pengujian dan rekap analisis',
                'is_system' => false,
                'requires_verification' => true,
            ]
        );
        $analystRole->permissions()->sync(Permission::whereIn('name', [
            'dashboard',
            'hasil_pengujian_rekap',
            'hasil_pengujian_riwayat_personal',
            'certificate_view',
            'certificate_download',
        ])->pluck('id'));

        // User
        $userRole = Role::firstOrCreate(
            ['name' => 'user'],
            [
                'description' => 'Pengguna biasa untuk pengajuan pengujian hasil pengujian',
                'is_system' => false,
                'requires_verification' => false,
            ]
        );
        $userRole->permissions()->sync(Permission::whereIn('name', [
            'dashboard',
            'berita_acara_create',
            'berita_acara_riwayat_personal',
            'hasil_pengujian_riwayat_personal',
            'payment_upload',
            'payment_discount',
            'payment_riwayat_personal',
            'discount_riwayat_personal',
            'certificate_view',
            'certificate_download',
        ])->pluck('id'));

        // Penyelia (Supervisor)
        $penyeliaRole = Role::firstOrCreate(
            ['name' => 'penyelia'],
            [
                'description' => 'Penyelia laboratorium untuk pengawasan dan verifikasi pengujian',
                'is_system' => false,
                'requires_verification' => true,
            ]
        );
        $penyeliaRole->permissions()->sync(Permission::whereIn('name', [
            'dashboard',
            'berita_acara_verify',
            'berita_acara_assign',
            'berita_acara_riwayat_semua',
            'berita_acara_riwayat_personal',
            'hasil_pengujian_rekap',
            'hasil_pengujian_riwayat_semua',
            'hasil_pengujian_riwayat_personal',
            'payment_confirm',
            'payment_riwayat_semua',
            'certificate_view',
            'certificate_download',
            'discount_verifikasi',
            'discount_riwayat_semua',
            'user_view',
            'user_verify',
        ])->pluck('id'));

        // Manager Teknis (Technical Manager)
        $managerTeknisRole = Role::firstOrCreate(
            ['name' => 'manager_teknis'],
            [
                'description' => 'Manager teknis untuk pengelolaan teknis laboratorium',
                'is_system' => false,
                'requires_verification' => true,
            ]
        );
        $managerTeknisRole->permissions()->sync(Permission::whereIn('name', [
            'dashboard',
            'berita_acara_riwayat_semua',
            'berita_acara_riwayat_personal',
            'hasil_pengujian_rekap',
            'hasil_pengujian_riwayat_semua',
            'hasil_pengujian_riwayat_personal',
            'payment_confirm',
            'payment_riwayat_semua',
            'certificate_view',
            'certificate_download',
            'form_manage_berita_acara',
            'form_manage_rekap_analis',
            'form_manage_perhitungan_biaya',
            'form_manage_kelola_harga_instrumen',
            'user_view',
            'discount_verifikasi',
            'discount_riwayat_semua',
        ])->pluck('id'));

        // Akun test
        $accounts = [
            ['email' => 'superadmin@lab.com', 'name' => 'Super Admin',     'role' => 'superadmin',     'is_verified' => true],
            ['email' => 'admin@lab.com',      'name' => 'Admin Lab',       'role' => 'admin',          'is_verified' => true],
            ['email' => 'analyst@lab.com',    'name' => 'Analis Lab',      'role' => 'analyst',        'is_verified' => true],
            ['email' => 'user@lab.com',       'name' => 'User Lab',        'role' => 'user',           'is_verified' => true],
            ['email' => 'penyelia@lab.com',   'name' => 'Penyelia Lab',    'role' => 'penyelia',       'is_verified' => true],
            ['email' => 'manager@lab.com',    'name' => 'Manager Teknis',  'role' => 'manager_teknis', 'is_verified' => true],
        ];
        foreach ($accounts as $acc) {
            User::firstOrCreate(
                ['email' => $acc['email']],
                [
                    'name' => $acc['name'],
                    'password' => bcrypt('password'),
                    'role' => $acc['role'],
                    'is_verified' => $acc['is_verified'],
                ]
            );
        }

        // Instrument prices
        $this->call(InstrumentPriceSeeder::class);

        // Form templates (needs superadmin user to exist)
        $this->call(FormTemplateSeeder::class);
    }
}
