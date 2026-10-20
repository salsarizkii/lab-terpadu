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
            ['name' => 'berita_acara_create', 'display_name' => 'Buat Berita Acara', 'group' => 'Berita Acara'],
            ['name' => 'berita_acara_verify', 'display_name' => 'Verifikasi Berita Acara', 'group' => 'Berita Acara'],
            ['name' => 'berita_acara_assign', 'display_name' => 'Menugaskan Analis', 'group' => 'Berita Acara'],
            ['name' => 'berita_acara_riwayat_personal', 'display_name' => 'Personal Riwayat Berita Acara', 'group' => 'Berita Acara'],
            ['name' => 'berita_acara_riwayat_semua', 'display_name' => 'Riwayat Seluruh Berita Acara', 'group' => 'Berita Acara'],

            // Payment
            ['name' => 'payment_upload', 'display_name' => 'Upload Bukti Bayar', 'group' => 'Pembayaran'],
            ['name' => 'payment_discount', 'display_name' => 'Ajukan Diskon Pembayaran', 'group' => 'Pembayaran'],
            ['name' => 'payment_confirm', 'display_name' => 'Konfirmasi Pembayaran', 'group' => 'Pembayaran'],
            ['name' => 'payment_riwayat_personal', 'display_name' => 'Personal Riwayat Pembayaran', 'group' => 'Pembayaran'],
            ['name' => 'payment_riwayat_semua', 'display_name' => 'Riwayat Seluruh Pembayaran', 'group' => 'Pembayaran'],

            // Hasil Pengujian
            ['name' => 'hasil_pengujian_rekap', 'display_name' => 'Buat Rekap Analis', 'group' => 'Hasil Pengujian'],
            ['name' => 'hasil_pengujian_riwayat_personal', 'display_name' => 'Personal Riwayat Rekap Analisis', 'group' => 'Hasil Pengujian'],
            ['name' => 'hasil_pengujian_riwayat_semua', 'display_name' => 'Riwayat Seluruh Rekap Analisis', 'group' => 'Hasil Pengujian'],

            // Certificate
            ['name' => 'certificate_view', 'display_name' => 'Melihat Sertifikat', 'group' => 'Sertifikat'],
            ['name' => 'certificate_download', 'display_name' => 'Download Sertifikat', 'group' => 'Sertifikat'],
            ['name' => 'certificate_upload', 'display_name' => 'Upload Sertifikat', 'group' => 'Sertifikat'],

            // Users
            ['name' => 'user_view', 'display_name' => 'Melihat Pengguna', 'group' => 'Pengguna'],
            ['name' => 'user_verify', 'display_name' => 'Verifikasi Pengguna', 'group' => 'Pengguna'],

            // Form
            ['name' => 'form_manage_berita_acara', 'display_name' => 'Kelola Form Berita Acara', 'group' => 'Form'],
            ['name' => 'form_manage_rekap_analis', 'display_name' => 'Kelola Form Rekap Analis', 'group' => 'Form'],
            ['name' => 'form_manage_perhitungan_biaya', 'display_name' => 'Kelola Form Perhitungan Biaya', 'group' => 'Form'],
            ['name' => 'form_manage_kelola_harga_instrumen', 'display_name' => 'Kelola Form Harga Instrumen', 'group' => 'Form'],

            // Discount
            ['name' => 'discount_verifikasi', 'display_name' => 'Verifikasi Diskon', 'group' => 'Diskon'],
            ['name' => 'discount_riwayat_semua', 'display_name' => 'Riwayat Seluruh Diskon', 'group' => 'Diskon'],
            ['name' => 'discount_riwayat_personal', 'display_name' => 'Personal Riwayat Diskon', 'group' => 'Diskon'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                $perm
            );
        }
    }
}
