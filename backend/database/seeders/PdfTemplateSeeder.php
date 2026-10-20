<?php

namespace Database\Seeders;

use App\Models\PdfTemplate;
use Illuminate\Database\Seeder;

class PdfTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'type' => 'berita_acara',
                'name' => 'Template PDF Berita Acara',
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'margin_right' => 15,
                'show_kop' => true,
                'show_ttd' => true,
                'show_rincian' => true,
                'catatan' => null,
                'settings' => null,
                'html_content' => <<<HTML
<!-- KOP_START -->
<div class="kop-surat">
    <table class="kop-table">
        <tr>
            <td style="width: 15%; text-align: center;">
                <strong style="font-size: 16pt;">LAB</strong>
            </td>
            <td style="width: 85%; text-align: center;">
                <h3 style="margin: 0; font-size: 14pt; text-transform: uppercase;">{{ kop_nama_lab }}</h3>
                <p style="margin: 2px 0; font-size: 10pt;">KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET, DAN TEKNOLOGI</p>
                <p style="margin: 0; font-size: 9pt; color: #475569;">{{ kop_alamat }} | Email: {{ kop_email }} | Telp: {{ kop_telepon }}</p>
            </td>
        </tr>
    </table>
</div>
<!-- KOP_END -->

<h3 class="text-center" style="text-decoration: underline; margin-bottom: 5px;">BERITA ACARA PENGAJUAN PENGUJIEN</h3>
<p class="text-center" style="margin-top: 0; font-size: 10pt; color: #475569;">No. Pengajuan: {{ id }}</p>

<table style="border: none; margin-top: 15px;">
    <tr style="border: none;"><td style="border: none; width: 30%; font-weight: bold;">Nama Pemohon</td><td style="border: none;">: {{ nama_pemohon }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">NIM / NIP</td><td style="border: none;">: {{ nim }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Prodi / Fakultas / Instansi</td><td style="border: none;">: {{ prodi_fakultas_instansi }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Dosen Pembimbing</td><td style="border: none;">: {{ dosen_pembimbing }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">No. Telepon / HP</td><td style="border: none;">: {{ telp_hp }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Email</td><td style="border: none;">: {{ email }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Alamat</td><td style="border: none;">: {{ alamat }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Judul Penelitian</td><td style="border: none;">: {{ judul_penelitian }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Bahan / Contoh Uji</td><td style="border: none;">: {{ bahan_contoh_uji }}</td></tr>
     <tr style="border: none;"><td style="border: none; font-weight: bold;">Jumlah Hasil Pengujian</td><td style="border: none;">: {{ jumlah_sampel }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Tanggal Pengajuan</td><td style="border: none;">: {{ tanggal_pengajuan }}</td></tr>
</table>

<!-- RINCIAN_START -->
<h4 style="margin-top: 20px; margin-bottom: 8px;">Daftar Hasil Pengujian & Parameter Uji:</h4>
{{ tabel_sampel }}

<div style="margin-top: 15px; font-weight: bold; font-size: 11pt;">
    Total Biaya Estimasi: <span style="color: #2563eb;">{{ total_biaya }}</span>
</div>
<!-- RINCIAN_END -->

<!-- TTD_START -->
<div style="margin-top: 40px;">
    <table style="border: none;">
        <tr style="border: none;">
            <td style="border: none; text-align: center; width: 50%;">
                <p>Pemohon Pengujian,</p>
                <div class="ttd-space"></div>
                <p><strong>({{ nama_pemohon }})</strong></p>
            </td>
            <td style="border: none; text-align: center; width: 50%;">
                <p>Petugas Penerima Hasil Pengujian,</p>
                <div class="ttd-space"></div>
                <p><strong>({{ nama_admin }})</strong></p>
            </td>
        </tr>
    </table>
</div>
<!-- TTD_END -->
HTML
            ],
            [
                'type' => 'rekap_analis',
                'name' => 'Template PDF Rekap Analisis',
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'margin_right' => 15,
                'show_kop' => true,
                'show_ttd' => true,
                'show_rincian' => true,
                'catatan' => null,
                'settings' => null,
                'html_content' => <<<HTML
<!-- KOP_START -->
<div class="kop-surat">
    <table class="kop-table">
        <tr>
            <td style="width: 85%; text-align: center;">
                <h3 style="margin: 0; font-size: 14pt; text-transform: uppercase;">{{ kop_nama_lab }}</h3>
                <p style="margin: 2px 0; font-size: 10pt;">LEMBAR KERJA & REKAP ANALISIS PENGUJIEN</p>
            </td>
        </tr>
    </table>
</div>
<!-- KOP_END -->

<h3 class="text-center" style="margin-bottom: 15px;">REKAP HASIL ANALISIS LABORATORIUM</h3>

<table style="border: none; margin-bottom: 20px;">
    <tr style="border: none;"><td style="border: none; width: 25%; font-weight: bold;">Kode Pengajuan</td><td style="border: none;">: {{ id }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Nama Pelanggan</td><td style="border: none;">: {{ nama_pemohon }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Instansi</td><td style="border: none;">: {{ instansi }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Tim Analis</td><td style="border: none;">: {{ nama_analyst }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Status Pengujian</td><td style="border: none;">: {{ status }}</td></tr>
</table>

<!-- RINCIAN_START -->
<h4>Hasil Pengujian Parameter:</h4>
{{ tabel_pengujian }}
<!-- RINCIAN_END -->

<div style="margin-top: 15px; padding: 10px; background-color: #f8fafc; border: 1px solid #e2e8f0;">
    <strong>Catatan / Keterangan Analis:</strong>
    <p style="margin: 5px 0 0 0;">{{ keterangan_analis }}</p>
</div>

<!-- TTD_START -->
<div style="margin-top: 40px; text-align: right;">
    <div style="display: inline-block; width: 240px; text-align: center;">
        <p>Analis Laboratorium,</p>
        <div class="ttd-space"></div>
        <p><strong>({{ nama_analyst }})</strong></p>
    </div>
</div>
<!-- TTD_END -->
HTML
            ],
            [
                'type' => 'tagihan',
                'name' => 'Template PDF Nota / Tagihan Pembayaran',
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'margin_right' => 15,
                'show_kop' => true,
                'show_ttd' => true,
                'show_rincian' => true,
                'catatan' => null,
                'settings' => null,
                'html_content' => <<<HTML
<!-- KOP_START -->
<div class="kop-surat">
    <table class="kop-table">
        <tr>
            <td style="width: 85%; text-align: center;">
                <h3 style="margin: 0; font-size: 14pt; text-transform: uppercase;">{{ kop_nama_lab }}</h3>
                <p style="margin: 2px 0; font-size: 10pt;">NOTA PERHITUNGAN BIAYA & TAGIHAN PENGUJIEN</p>
            </td>
        </tr>
    </table>
</div>
<!-- KOP_END -->

<h3 class="text-center" style="margin-bottom: 5px;">NOTA PEMBAYARAN</h3>
<p class="text-center" style="margin-top: 0; font-size: 10pt; color: #475569;">No: {{ no_tagihan }}</p>

<table style="border: none; margin-top: 15px;">
    <tr style="border: none;"><td style="border: none; width: 25%; font-weight: bold;">Ditujukan Kepada</td><td style="border: none;">: {{ nama_pemohon }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Prodi / Instansi</td><td style="border: none;">: {{ instansi }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Tanggal Pembayaran</td><td style="border: none;">: {{ tanggal_pembayaran }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Status Pembayaran</td><td style="border: none;">: <span style="font-weight: bold; color: #16a34a;">{{ status_pembayaran }}</span></td></tr>
</table>

<!-- RINCIAN_START -->
<h4 style="margin-top: 20px; margin-bottom: 8px;">Rincian Biaya Pengujian:</h4>
{{ rincian_biaya_tabel }}

<table style="border: none; width: 50%; float: right; margin-top: 10px;">
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Subtotal:</td><td style="border: none; text-align: right;">{{ subtotal }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold; color: #dc2626;">Diskon:</td><td style="border: none; text-align: right; color: #dc2626;">- {{ diskon }}</td></tr>
    <tr style="border: top 2px solid #000;"><td style="border: none; font-weight: bold; font-size: 12pt;">TOTAL BAYAR:</td><td style="border: none; text-align: right; font-weight: bold; font-size: 12pt; color: #2563eb;">{{ total_bayar }}</td></tr>
</table>
<div style="clear: both;"></div>
<!-- RINCIAN_END -->

<!-- TTD_START -->
<div style="margin-top: 40px;">
    <table style="border: none;">
        <tr style="border: none;">
            <td style="border: none; text-align: center; width: 50%;">
                <p>Pembayar,</p>
                <div class="ttd-space"></div>
                <p><strong>({{ nama_pemohon }})</strong></p>
            </td>
            <td style="border: none; text-align: center; width: 50%;">
                <p>Bendahara / Admin UPT,</p>
                <div class="ttd-space"></div>
                <p><strong>({{ nama_bendahara }})</strong></p>
            </td>
        </tr>
    </table>
</div>
<!-- TTD_END -->
HTML
            ],
            [
                'type' => 'sertifikat',
                'name' => 'Template PDF Sertifikat / Laporan Hasil Uji (LHU)',
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'margin_right' => 15,
                'show_kop' => true,
                'show_ttd' => true,
                'show_rincian' => true,
                'catatan' => null,
                'settings' => null,
                'html_content' => <<<HTML
<!-- KOP_START -->
<div class="kop-surat">
    <table class="kop-table">
        <tr>
            <td style="width: 85%; text-align: center;">
                <h3 style="margin: 0; font-size: 15pt; text-transform: uppercase; letter-spacing: 1px;">{{ kop_nama_lab }}</h3>
                <p style="margin: 2px 0; font-size: 10pt;">KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET, DAN TEKNOLOGI</p>
                <p style="margin: 0; font-size: 9pt; color: #475569;">{{ kop_alamat }} | Email: {{ kop_email }} | Telp: {{ kop_telepon }}</p>
            </td>
        </tr>
    </table>
</div>
<!-- KOP_END -->

<h3 class="text-center" style="margin-bottom: 5px; font-size: 14pt;">LAPORAN HASIL UJI</h3>
<p class="text-center" style="margin-top: 0; font-size: 10pt; color: #475569;">No. LHU: {{ no_sertifikat }}</p>

<table style="border: none; margin-top: 20px;">
    <tr style="border: none;"><td style="border: none; width: 30%; font-weight: bold;">Nama Pelanggan / Pemohon</td><td style="border: none;">: {{ nama_pemohon }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Instansi / Fakultas</td><td style="border: none;">: {{ instansi }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Judul Penelitian</td><td style="border: none;">: {{ judul_penelitian }}</td></tr>
     <tr style="border: none;"><td style="border: none; font-weight: bold;">Jenis / Bahan Hasil Pengujian</td><td style="border: none;">: {{ jenis_sampel }}</td></tr>
     <tr style="border: none;"><td style="border: none; font-weight: bold;">Jumlah Hasil Pengujian</td><td style="border: none;">: {{ jumlah_sampel }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Tanggal Selesai Uji</td><td style="border: none;">: {{ tanggal_selesai }}</td></tr>
</table>

<!-- RINCIAN_START -->
<h4 style="margin-top: 20px; margin-bottom: 10px;">HASIL PENGUJIAN LABORATORIUM:</h4>
{{ tabel_hasil_uji }}
<!-- RINCIAN_END -->

<p style="font-size: 9pt; color: #64748b; margin-top: 15px;">* Laporan Hasil Uji ini hanya berlaku untuk hasil pengujian yang diuji di {{ kop_nama_lab }}.</p>

<!-- TTD_START -->
<!-- Area Tanda Tangan Basah Kepala UPT (Dikosongkan untuk TTD Manual) -->
<div style="margin-top: 40px; text-align: right;">
    <div style="display: inline-block; width: 280px; text-align: center;">
        <p>Bandar Lampung, {{ tanggal_cetak }}</p>
        <p style="margin-bottom: 0;"><strong>Kepala UPT Laboratorium Terpadu</strong></p>
        <div style="height: 90px; border-bottom: 1px dashed #cbd5e1; margin: 10px 0;">
            <span style="font-size: 8pt; color: #94a3b8; line-height: 90px;">(Area Tanda Tangan Basah)</span>
        </div>
        <p style="margin-top: 5px;"><strong>{{ kepala_upt }}</strong></p>
        <p style="margin-top: 2px; font-size: 9pt; color: #475569;">NIP. {{ nip_kepala_upt }}</p>
    </div>
</div>
<!-- TTD_END -->
HTML
            ],
            [
                'type' => 'pengambilan_sisa_sampel',
                'name' => 'Template PDF Form Pengambilan Sisa Sampel',
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'margin_right' => 15,
                'show_kop' => true,
                'show_ttd' => true,
                'show_rincian' => true,
                'catatan' => null,
                'settings' => null,
                'html_content' => <<<HTML
<!-- KOP_START -->
<div class="kop-surat">
    <table class="kop-table">
        <tr>
            <td style="width: 85%; text-align: center;">
                <h3 style="margin: 0; font-size: 14pt; text-transform: uppercase;">{{ kop_nama_lab }}</h3>
                <p style="margin: 2px 0; font-size: 10pt;">KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET, DAN TEKNOLOGI</p>
                <p style="margin: 0; font-size: 9pt; color: #475569;">{{ kop_alamat }} | Email: {{ kop_email }} | Telp: {{ kop_telepon }}</p>
            </td>
        </tr>
    </table>
</div>
<!-- KOP_END -->

<h3 class="text-center" style="text-decoration: underline; margin-bottom: 5px;">FORM PENGAMBILAN SISA SAMPEL/BAHAN</h3>
<p class="text-center" style="margin-top: 0; font-size: 10pt; color: #475569;">No. Form: {{ nomor_form }}</p>

<table style="border: none; margin-top: 15px;">
    <tr style="border: none;"><td style="border: none; width: 30%; font-weight: bold;">Nama Pemohon</td><td style="border: none;">: {{ nama_pemohon }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">NIM / NIP</td><td style="border: none;">: {{ nim }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Prodi / Fakultas / Instansi</td><td style="border: none;">: {{ instansi }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Judul Penelitian</td><td style="border: none;">: {{ judul_penelitian }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Jenis / Bahan Hasil Pengujian</td><td style="border: none;">: {{ jenis_sampel }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Jumlah Hasil Pengujian</td><td style="border: none;">: {{ jumlah_sampel }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Jumlah Sisa Sampel/Bahan</td><td style="border: none;">: {{ sisa_sampel }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Tanggal Pengambilan</td><td style="border: none;">: {{ tanggal_pengambilan }}</td></tr>
</table>

<!-- RINCIAN_START -->
<h4 style="margin-top: 20px; margin-bottom: 8px;">Daftar Sisa Sampel/Bahan yang Diambil:</h4>
{{ tabel_sisa_sampel }}
<!-- RINCIAN_END -->

<p style="margin-top: 15px; font-size: 10pt; text-align: justify;">
    Dengan ini menyatakan bahwa sisa sampel/bahan milik pemohon di atas telah diterima kembali secara lengkap
    dan {{ kop_nama_lab }} tidak menyimpan sisa hasil pengujian dimaksud setelah tanggal pengambilan.
</p>

<!-- TTD_START -->
<div style="margin-top: 40px;">
    <table style="border: none;">
        <tr style="border: none;">
            <td style="border: none; text-align: center; width: 50%;">
                <p>Pemohon / Penerima,</p>
                <div class="ttd-space"></div>
                <p><strong>({{ nama_pemohon }})</strong></p>
            </td>
            <td style="border: none; text-align: center; width: 50%;">
                <p>Petugas Laboratorium,</p>
                <div class="ttd-space"></div>
                <p><strong>({{ nama_admin }})</strong></p>
            </td>
        </tr>
    </table>
</div>
<!-- TTD_END -->
            [
                'type' => 'lembar_kendali_sampel',
                'name' => 'Template PDF Lembar Kendali Sampel',
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'margin_right' => 15,
                'show_kop' => true,
                'show_ttd' => true,
                'show_rincian' => true,
                'catatan' => null,
                'settings' => null,
                'html_content' => <<<HTML
<!-- KOP_START -->
<div class="kop-surat">
    <table class="kop-table">
        <tr>
            <td style="width: 85%; text-align: center;">
                <h3 style="margin: 0; font-size: 14pt; text-transform: uppercase;">{{ kop_nama_lab }}</h3>
                <p style="margin: 2px 0; font-size: 10pt;">KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET, DAN TEKNOLOGI</p>
                <p style="margin: 0; font-size: 9pt; color: #475569;">{{ kop_alamat }} | Email: {{ kop_email }} | Telp: {{ kop_telepon }}</p>
            </td>
        </tr>
    </table>
</div>
<!-- KOP_END -->

<h3 class="text-center" style="text-decoration: underline; margin-bottom: 5px;">LEMBAR KENDALI SAMPEL / CONTOH UJI</h3>

<table style="border: none; margin-top: 15px;">
    <tr style="border: none;"><td style="border: none; width: 30%; font-weight: bold;">Tanggal Terima Sampel</td><td style="border: none;">: {{ tanggal_berita_acara }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Agenda No.</td><td style="border: none;">: {{ agenda }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Asal Sampel</td><td style="border: none;">: {{ agenda }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Metode/Instrumen</td><td style="border: none;">: {{ metode_instrumen }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Jumlah Sampel</td><td style="border: none;">: {{ jumlah_sampel_contoh_uji }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Jumlah Pengukuran</td><td style="border: none;">: {{ pengukuran_pengulangan_per_sampel }}</td></tr>
</table>
HTML
            ],
        ];

        foreach ($templates as $t) {
            PdfTemplate::updateOrCreate(
                ['type' => $t['type']],
                $t
            );
        }
    }
}
