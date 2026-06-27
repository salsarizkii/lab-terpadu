<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'dashboard', 'display_name' => 'Dashboard', 'group' => 'Umum'],

            // Berita Acara
            ['name' => 'berita_acara_view', 'display_name' => 'Melihat Berita Acara', 'group' => 'Berita Acara'],
            ['name' => 'berita_acara_create', 'display_name' => 'Membuat Berita Acara', 'group' => 'Berita Acara'],
            ['name' => 'berita_acara_verify', 'display_name' => 'Verifikasi Berita Acara', 'group' => 'Berita Acara'],
            ['name' => 'berita_acara_assign', 'display_name' => 'Menugaskan Analis', 'group' => 'Berita Acara'],

            // Payment
            ['name' => 'payment_view', 'display_name' => 'Melihat Pembayaran', 'group' => 'Pembayaran'],
            ['name' => 'payment_upload', 'display_name' => 'Upload Bukti Bayar', 'group' => 'Pembayaran'],
            ['name' => 'payment_discount', 'display_name' => 'Diskon Pembayaran', 'group' => 'Pembayaran'],
            ['name' => 'payment_confirm', 'display_name' => 'Konfirmasi Pembayaran', 'group' => 'Pembayaran'],

            // Sample
            ['name' => 'sample_view', 'display_name' => 'Melihat Sampel', 'group' => 'Sampel'],
            ['name' => 'sample_input', 'display_name' => 'Input Data Sampel', 'group' => 'Sampel'],
            ['name' => 'sample_status', 'display_name' => 'Update Status Sampel', 'group' => 'Sampel'],

            // Pengujian
            ['name' => 'pengujian_input', 'display_name' => 'Input Data Pengujian', 'group' => 'Pengujian'],

            // Certificate
            ['name' => 'certificate_view', 'display_name' => 'Melihat Sertifikat', 'group' => 'Sertifikat'],
            ['name' => 'certificate_download', 'display_name' => 'Download Sertifikat', 'group' => 'Sertifikat'],

            // Users
            ['name' => 'user_view', 'display_name' => 'Melihat Pengguna', 'group' => 'Pengguna'],
            ['name' => 'user_verify', 'display_name' => 'Verifikasi Pengguna', 'group' => 'Pengguna'],

            // Roles
            ['name' => 'role_manage', 'display_name' => 'Kelola Role', 'group' => 'Role'],

            // Form
            ['name' => 'form_view', 'display_name' => 'Melihat Form', 'group' => 'Form'],
            ['name' => 'form_manage_berita_acara', 'display_name' => 'Kelola Form Berita Acara', 'group' => 'Form'],
            ['name' => 'form_manage_rekap_analis', 'display_name' => 'Kelola Form Rekap Analis', 'group' => 'Form'],
            ['name' => 'form_fill', 'display_name' => 'Mengisi Form', 'group' => 'Form'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                $perm
            );
        }
    }
}
