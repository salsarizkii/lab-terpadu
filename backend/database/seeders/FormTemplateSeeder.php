<?php

namespace Database\Seeders;

use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class FormTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::where('email', 'superadmin@lab.com')->first();

        // Default template untuk Berita Acara
        $ba = FormTemplate::firstOrCreate(
            ['type' => 'berita_acara'],
            [
                'name' => 'Formulir Berita Acara',
                'description' => 'Formulir standar untuk pengajuan berita acara pengujian sampel',
                'is_active' => true,
                'created_by' => $superadmin?->id ?? 1,
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
                ['label' => 'Judul Penelitian', 'type' => 'textarea', 'is_required' => true, 'sort_order' => 9],
                ['label' => 'Akses Lab', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 10],
                ['label' => 'Bahan / Contoh Uji', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 11],
                ['label' => 'Kemasan / Packing', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 12],
                ['label' => 'Metode / Instrumen', 'type' => 'text', 'is_required' => false, 'sort_order' => 13],
                ['label' => 'Jumlah Sampel', 'type' => 'number', 'is_required' => false, 'sort_order' => 14],
                ['label' => 'Kode / Nama Sampel', 'type' => 'text', 'is_required' => false, 'sort_order' => 15],
                ['label' => 'Keterangan Sampel', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 16],
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
                ['label' => 'Data Sampel', 'type' => 'heading', 'is_required' => false, 'sort_order' => 4],
                ['label' => 'Kode Sampel', 'type' => 'text', 'is_required' => true, 'sort_order' => 5],
                ['label' => 'Jenis Sampel', 'type' => 'text', 'is_required' => false, 'sort_order' => 6],
                ['label' => 'Jumlah Sampel', 'type' => 'number', 'is_required' => false, 'sort_order' => 7],
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
    }
}
