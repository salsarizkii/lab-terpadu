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
                'html_content' => <<<HTML
<div class="kop-surat">
    <table class="kop-table">
        <tr>
            <td style="width: 15%; text-align: center;">
                <strong style="font-size: 16pt;">LAB</strong>
            </td>
            <td style="width: 85%; text-align: center;">
                <h3 style="margin: 0; font-size: 14pt; text-transform: uppercase;">UPT LABORATORIUM TERPADU</h3>
                <p style="margin: 2px 0; font-size: 10pt;">KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET, DAN TEKNOLOGI</p>
                <p style="margin: 0; font-size: 9pt; color: #475569;">Jl. Prof. Dr. Sumantri Brojonegoro No. 1 | Email: labterpadu@univ.ac.id</p>
            </td>
        </tr>
    </table>
</div>

<h3 class="text-center" style="text-decoration: underline; margin-bottom: 5px;">BERITA ACARA PENGAJUAN PENGUJIEN</h3>
<p class="text-center" style="margin-top: 0; font-size: 10pt; color: #475569;">No. Pengajuan: {{ id }}</p>

<table style="border: none; margin-top: 15px;">
    <tr style="border: none;"><td style="border: none; width: 30%; font-weight: bold;">Nama Pemohon</td><td style="border: none;">: {{ nama_pemohon }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">NIM / NIP</td><td style="border: none;">: {{ nim }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Prodi / Fakultas / Instansi</td><td style="border: none;">: {{ prodi_fakultas_instansi }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Dosen Pembimbing</td><td style="border: none;">: {{ dosen_pembimbing }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">No. Telepon / HP</td><td style="border: none;">: {{ telp_hp }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Email</td><td style="border: none;">: {{ email }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Judul Penelitian</td><td style="border: none;">: {{ judul_penelitian }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Bahan / Contoh Uji</td><td style="border: none;">: {{ bahan_contoh_uji }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Jumlah Sampel</td><td style="border: none;">: {{ jumlah_sampel }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Tanggal Pengajuan</td><td style="border: none;">: {{ tanggal_pengajuan }}</td></tr>
</table>

<h4 style="margin-top: 20px; margin-bottom: 8px;">Daftar Sampel & Parameter Uji:</h4>
{{ tabel_sampel }}

<div style="margin-top: 15px; font-weight: bold; font-size: 11pt;">
    Total Biaya Estimasi: <span style="color: #2563eb;">{{ total_biaya }}</span>
</div>

<div style="margin-top: 40px;">
    <table style="border: none;">
        <tr style="border: none;">
            <td style="border: none; text-align: center; width: 50%;">
                <p>Pemohon Pengujian,</p>
                <div class="ttd-space"></div>
                <p><strong>({{ nama_pemohon }})</strong></p>
            </td>
            <td style="border: none; text-align: center; width: 50%;">
                <p>Petugas Penerima Sample,</p>
                <div class="ttd-space"></div>
                <p><strong>(Admin UPT Lab Terpadu)</strong></p>
            </td>
        </tr>
    </table>
</div>
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
                'html_content' => <<<HTML
<div class="kop-surat">
    <table class="kop-table">
        <tr>
            <td style="width: 85%; text-align: center;">
                <h3 style="margin: 0; font-size: 14pt; text-transform: uppercase;">UPT LABORATORIUM TERPADU</h3>
                <p style="margin: 2px 0; font-size: 10pt;">LEMBAR KERJA & REKAP ANALISIS PENGUJIEN</p>
            </td>
        </tr>
    </table>
</div>

<h3 class="text-center" style="margin-bottom: 15px;">REKAP HASIL ANALISIS LABORATORIUM</h3>

<table style="border: none; margin-bottom: 20px;">
    <tr style="border: none;"><td style="border: none; width: 25%; font-weight: bold;">Kode Pengajuan</td><td style="border: none;">: {{ id }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Nama Pelanggan</td><td style="border: none;">: {{ nama_pemohon }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Instansi</td><td style="border: none;">: {{ instansi }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Tim Analis</td><td style="border: none;">: {{ nama_analyst }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Status Pengujian</td><td style="border: none;">: {{ status }}</td></tr>
</table>

<h4>Hasil Pengujian Parameter:</h4>
{{ tabel_pengujian }}

<div style="margin-top: 15px; padding: 10px; background-color: #f8fafc; border: 1px solid #e2e8f0;">
    <strong>Catatan / Keterangan Analis:</strong>
    <p style="margin: 5px 0 0 0;">{{ keterangan_analis }}</p>
</div>

<div style="margin-top: 40px; text-align: right;">
    <div style="display: inline-block; width: 240px; text-align: center;">
        <p>Analis Laboratorium,</p>
        <div class="ttd-space"></div>
        <p><strong>({{ nama_analyst }})</strong></p>
    </div>
</div>
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
                'html_content' => <<<HTML
<div class="kop-surat">
    <table class="kop-table">
        <tr>
            <td style="width: 85%; text-align: center;">
                <h3 style="margin: 0; font-size: 14pt; text-transform: uppercase;">UPT LABORATORIUM TERPADU</h3>
                <p style="margin: 2px 0; font-size: 10pt;">NOTA PERHITUNGAN BIAYA & TAGIHAN PENGUJIEN</p>
            </td>
        </tr>
    </table>
</div>

<h3 class="text-center" style="margin-bottom: 5px;">NOTA PEMBAYARAN</h3>
<p class="text-center" style="margin-top: 0; font-size: 10pt; color: #475569;">No: {{ no_tagihan }}</p>

<table style="border: none; margin-top: 15px;">
    <tr style="border: none;"><td style="border: none; width: 25%; font-weight: bold;">Ditujukan Kepada</td><td style="border: none;">: {{ nama_pemohon }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Prodi / Instansi</td><td style="border: none;">: {{ instansi }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Tanggal Pembayaran</td><td style="border: none;">: {{ tanggal_pembayaran }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Status Pembayaran</td><td style="border: none;">: <span style="font-weight: bold; color: #16a34a;">{{ status_pembayaran }}</span></td></tr>
</table>

<h4 style="margin-top: 20px; margin-bottom: 8px;">Rincian Biaya Pengujian:</h4>
{{ rincian_biaya_tabel }}

<table style="border: none; width: 50%; float: right; margin-top: 10px;">
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Subtotal:</td><td style="border: none; text-align: right;">{{ subtotal }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold; color: #dc2626;">Diskon:</td><td style="border: none; text-align: right; color: #dc2626;">- {{ diskon }}</td></tr>
    <tr style="border: top 2px solid #000;"><td style="border: none; font-weight: bold; font-size: 12pt;">TOTAL BAYAR:</td><td style="border: none; text-align: right; font-weight: bold; font-size: 12pt; color: #2563eb;">{{ total_bayar }}</td></tr>
</table>
<div style="clear: both;"></div>

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
                <p><strong>(Petugas Keuangan)</strong></p>
            </td>
        </tr>
    </table>
</div>
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
                'html_content' => <<<HTML
<div class="kop-surat">
    <table class="kop-table">
        <tr>
            <td style="width: 85%; text-align: center;">
                <h3 style="margin: 0; font-size: 15pt; text-transform: uppercase; letter-spacing: 1px;">UPT LABORATORIUM TERPADU</h3>
                <p style="margin: 2px 0; font-size: 10pt;">KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET, DAN TEKNOLOGI</p>
                <p style="margin: 0; font-size: 9pt; color: #475569;">SERTIFIKAT / LAPORAN HASIL UJI (LHU)</p>
            </td>
        </tr>
    </table>
</div>

<h3 class="text-center" style="margin-bottom: 5px; font-size: 14pt;">LAPORAN HASIL UJI</h3>
<p class="text-center" style="margin-top: 0; font-size: 10pt; color: #475569;">No. LHU: {{ no_sertifikat }}</p>

<table style="border: none; margin-top: 20px;">
    <tr style="border: none;"><td style="border: none; width: 30%; font-weight: bold;">Nama Pelanggan / Pemohon</td><td style="border: none;">: {{ nama_pemohon }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Instansi / Fakultas</td><td style="border: none;">: {{ instansi }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Judul Penelitian</td><td style="border: none;">: {{ judul_penelitian }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Jenis / Bahan Sampel</td><td style="border: none;">: {{ jenis_sampel }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Jumlah Sampel</td><td style="border: none;">: {{ jumlah_sampel }}</td></tr>
    <tr style="border: none;"><td style="border: none; font-weight: bold;">Tanggal Selesai Uji</td><td style="border: none;">: {{ tanggal_selesai }}</td></tr>
</table>

<h4 style="margin-top: 20px; margin-bottom: 10px;">HASIL PENGUJIAN LABORATORIUM:</h4>
{{ tabel_hasil_uji }}

<p style="font-size: 9pt; color: #64748b; margin-top: 15px;">* Laporan Hasil Uji ini hanya berlaku untuk sampel yang diuji di UPT Laboratorium Terpadu.</p>

<!-- Area Tanda Tangan Basah Kepala UPT (Dikosongkan untuk TTD Manual) -->
<div style="margin-top: 40px; text-align: right;">
    <div style="display: inline-block; width: 280px; text-align: center;">
        <p>Bandar Lampung, {{ tanggal_cetak }}</p>
        <p style="margin-bottom: 0;"><strong>Kepala UPT Laboratorium Terpadu</strong></p>
        <div style="height: 90px; border-bottom: 1px dashed #cbd5e1; margin: 10px 0;">
            <span style="font-size: 8pt; color: #94a3b8; line-height: 90px;">(Area Tanda Tangan Basah)</span>
        </div>
        <p style="margin-top: 5px;"><strong>Prof. Dr. Kepala UPT, M.Si</strong></p>
        <p style="margin-top: 2px; font-size: 9pt; color: #475569;">NIP. 197501012000121001</p>
    </div>
</div>
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
