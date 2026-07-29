<?php

namespace Database\Seeders;

use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class FormTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::where('role', 'superadmin')->first();
        if (!$superadmin) return;

        // Default template untuk Berita Acara
        $ba = FormTemplate::firstOrCreate(
            ['type' => 'berita_acara'],
            [
                'name' => 'Formulir Berita Acara',
                'description' => 'Formulir standar untuk pengajuan berita acara pengujian hasil pengujian',
                'is_active' => true,
                'created_by' => $superadmin?->id,
            ]
        );

        if ($ba->fields()->count() === 0) {
            $fields = [
                ['label' => 'Informasi Pemohon', 'type' => 'heading', 'is_required' => false, 'sort_order' => 0],
                ['label' => 'Nama Pemohon', 'type' => 'text', 'is_required' => true, 'sort_order' => 1],
                ['label' => 'NIM', 'type' => 'text', 'is_required' => false, 'sort_order' => 2],
                ['label' => 'Prodi / Fakultas / Instansi', 'type' => 'text', 'is_required' => false, 'sort_order' => 3],
                ['label' => 'Dosen Pembimbing', 'type' => 'text', 'is_required' => false, 'sort_order' => 4],
                ['label' => 'No. Telp / HP', 'type' => 'phone', 'is_required' => false, 'sort_order' => 5],
                ['label' => 'Email', 'type' => 'email', 'is_required' => false, 'sort_order' => 6],
                ['label' => 'Alamat', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 7],
                ['label' => 'Informasi Penelitian', 'type' => 'heading', 'is_required' => false, 'sort_order' => 8],
                ['label' => 'Judul Penelitian', 'type' => 'textarea', 'is_required' => true, 'sort_order' => 9, 'locked' => true],
                ['label' => 'Akses Lab', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 10],
                ['label' => 'Bahan / Contoh Uji', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 11],
                ['label' => 'Kemasan / Packing', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 12],
                ['label' => 'Metode / Instrumen', 'type' => 'text', 'is_required' => false, 'sort_order' => 13],
                ['label' => 'Jumlah Hasil Pengujian', 'type' => 'number', 'is_required' => false, 'sort_order' => 14],
                ['label' => 'Kode / Nama Hasil Pengujian', 'type' => 'text', 'is_required' => false, 'sort_order' => 15],
                ['label' => 'Keterangan Hasil Pengujian', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 16],
                ['label' => 'Keterangan Tambahan', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 17],
            ];

            foreach ($fields as $f) {
                $ba->fields()->create($f);
            }
        }

        // Default template untuk Rekap Analis
        $ra = FormTemplate::firstOrCreate(
            ['type' => 'rekap_analis'],
            [
                'name' => 'Formulir Rekap Analis',
                'description' => 'Formulir standar untuk rekap data hasil analis',
                'is_active' => true,
                'created_by' => $superadmin?->id ?? 1,
            ]
        );

        if ($ra->fields()->count() === 0) {
            $fields = [
                ['label' => 'Informasi Analis', 'type' => 'heading', 'is_required' => false, 'sort_order' => 0],
                ['label' => 'Nama Analis', 'type' => 'text', 'is_required' => true, 'sort_order' => 1],
                ['label' => 'Lab / Unit', 'type' => 'text', 'is_required' => false, 'sort_order' => 2],
                ['label' => 'Tanggal Pengujian', 'type' => 'date', 'is_required' => true, 'sort_order' => 3],
                ['label' => 'Data Hasil Pengujian', 'type' => 'heading', 'is_required' => false, 'sort_order' => 4],
                ['label' => 'Kode Hasil Pengujian', 'type' => 'text', 'is_required' => true, 'sort_order' => 5],
                ['label' => 'Jenis Hasil Pengujian', 'type' => 'text', 'is_required' => false, 'sort_order' => 6],
                ['label' => 'Jumlah Hasil Pengujian', 'type' => 'number', 'is_required' => false, 'sort_order' => 7],
                ['label' => 'Hasil Pengujian', 'type' => 'heading', 'is_required' => false, 'sort_order' => 8],
                ['label' => 'Parameter Uji', 'type' => 'text', 'is_required' => true, 'sort_order' => 9],
                ['label' => 'Metode Pengujian', 'type' => 'text', 'is_required' => false, 'sort_order' => 10],
                ['label' => 'Hasil', 'type' => 'textarea', 'is_required' => true, 'sort_order' => 11],
                ['label' => 'Keterangan', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 12],
            ];

            foreach ($fields as $f) {
                $ra->fields()->create($f);
            }
        }

        // Default template untuk Perhitungan Biaya
        $pb = FormTemplate::firstOrCreate(
            ['type' => 'perhitungan_biaya'],
            [
                'name' => 'Formulir Perhitungan Biaya',
                'description' => 'Formulir untuk perhitungan biaya pengujian dan analisis',
                'is_active' => true,
                'created_by' => $superadmin?->id ?? 1,
            ]
        );

        if ($pb->fields()->count() === 0) {
            $fields = [
                ['label' => 'Informasi Pengujian', 'type' => 'heading', 'is_required' => false, 'sort_order' => 0],
                ['label' => 'Nama Pengujian', 'type' => 'text', 'is_required' => true, 'sort_order' => 1],
                ['label' => 'Jenis Hasil Pengujian', 'type' => 'text', 'is_required' => false, 'sort_order' => 2],
                ['label' => 'Jumlah Hasil Pengujian', 'type' => 'number', 'is_required' => true, 'sort_order' => 3],
                ['label' => 'Biaya Pengujian', 'type' => 'number', 'is_required' => true, 'sort_order' => 4],
                ['label' => 'Diskon', 'type' => 'number', 'is_required' => false, 'sort_order' => 5],
                ['label' => 'Total Biaya', 'type' => 'number', 'is_required' => true, 'sort_order' => 6],
                ['label' => 'Keterangan', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 7],
            ];

            foreach ($fields as $f) {
                $pb->fields()->create($f);
            }
        }

        // Default template untuk Kelola Harga Instrumen
        $hi = FormTemplate::firstOrCreate(
            ['type' => 'kelola_harga_instrumen'],
            [
                'name' => 'Formulir Kelola Harga Instrumen',
                'description' => 'Formulir untuk mengelola harga instrumen dan tarif pengujian',
                'is_active' => true,
                'created_by' => $superadmin?->id ?? 1,
            ]
        );

        if ($hi->fields()->count() === 0) {
            $fields = [
                ['label' => 'Informasi Instrumen', 'type' => 'heading', 'is_required' => false, 'sort_order' => 0],
                ['label' => 'Nama Instrumen', 'type' => 'text', 'is_required' => true, 'sort_order' => 1],
                ['label' => 'Merk / Tipe', 'type' => 'text', 'is_required' => false, 'sort_order' => 2],
                ['label' => 'Lokasi', 'type' => 'text', 'is_required' => false, 'sort_order' => 3],
                ['label' => 'Harga Sewa per Jam', 'type' => 'number', 'is_required' => true, 'sort_order' => 4],
                ['label' => 'Harga Sewa per Hasil Pengujian', 'type' => 'number', 'is_required' => false, 'sort_order' => 5],
                ['label' => 'Keterangan', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 6],
            ];

            foreach ($fields as $f) {
                $hi->fields()->create($f);
            }
        }
    }
}
