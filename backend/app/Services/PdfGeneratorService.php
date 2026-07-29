<?php

namespace App\Services;

use App\Models\PdfTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\IOFactory;

class PdfGeneratorService
{
    /**
     * Dapatkan daftar variabel dinamis yang tersedia per jenis dokumen.
     */
    public static function getAvailableVariables(string $type): array
    {
        $common = [
            ['key' => '{{ tanggal_cetak }}', 'label' => 'Tanggal Cetak Dokumen', 'example' => '30 Juli 2026'],
        ];

        switch ($type) {
            case 'berita_acara':
                return array_merge($common, [
                    ['key' => '{{ id }}', 'label' => 'ID Berita Acara', 'example' => 'BA-2026-001'],
                    ['key' => '{{ nama_pemohon }}', 'label' => 'Nama Pemohon', 'example' => 'Budi Santoso'],
                    ['key' => '{{ nim }}', 'label' => 'NIM / NIP', 'example' => '200101001'],
                    ['key' => '{{ prodi_fakultas_instansi }}', 'label' => 'Prodi / Fakultas / Instansi', 'example' => 'Biologi / FMIPA'],
                    ['key' => '{{ dosen_pembimbing }}', 'label' => 'Dosen Pembimbing', 'example' => 'Dr. Ahmad, M.Si'],
                    ['key' => '{{ telp_hp }}', 'label' => 'No. HP / Telepon', 'example' => '081234567890'],
                    ['key' => '{{ email }}', 'label' => 'Email', 'example' => 'budi@example.com'],
                    ['key' => '{{ alamat }}', 'label' => 'Alamat Pemohon', 'example' => 'Jl. Merdeka No. 10'],
                    ['key' => '{{ judul_penelitian }}', 'label' => 'Judul Penelitian', 'example' => 'Analisis Kualitas Air Sungai'],
                    ['key' => '{{ bahan_contoh_uji }}', 'label' => 'Bahan / Contoh Uji', 'example' => 'Air Sampel 500ml'],
                    ['key' => '{{ jumlah_sampel }}', 'label' => 'Jumlah Sampel', 'example' => '3 Sampel'],
                    ['key' => '{{ status_pelanggan }}', 'label' => 'Status Pelanggan', 'example' => 'Internal / Mahasiswa'],
                    ['key' => '{{ status_berkas }}', 'label' => 'Status Berkas', 'example' => 'Disetujui'],
                    ['key' => '{{ tanggal_pengajuan }}', 'label' => 'Tanggal Pengajuan', 'example' => '25 Juli 2026'],
                    ['key' => '{{ total_biaya }}', 'label' => 'Total Biaya (Rp)', 'example' => 'Rp 500.000'],
                    ['key' => '{{ tabel_sampel }}', 'label' => 'Tabel Daftar Sampel (HTML)', 'example' => '[Tabel Sampel & Parameter]'],
                ]);

            case 'rekap_analis':
                return array_merge($common, [
                    ['key' => '{{ id }}', 'label' => 'ID Berita Acara', 'example' => 'BA-2026-001'],
                    ['key' => '{{ nama_pemohon }}', 'label' => 'Nama Pemohon', 'example' => 'Budi Santoso'],
                    ['key' => '{{ instansi }}', 'label' => 'Instansi', 'example' => 'Universitas X'],
                    ['key' => '{{ nama_analyst }}', 'label' => 'Nama Analis / Tim Analis', 'example' => 'Dr. Rina, M.Sc'],
                    ['key' => '{{ status }}', 'label' => 'Status Pengujian', 'example' => 'Selesai Analisis'],
                    ['key' => '{{ tanggal_pengajuan }}', 'label' => 'Tanggal Pengajuan', 'example' => '25 Juli 2026'],
                    ['key' => '{{ tabel_pengujian }}', 'label' => 'Tabel Hasil Pengujian Analis (HTML)', 'example' => '[Tabel Parameter & Nilai Hasil]'],
                    ['key' => '{{ keterangan_analis }}', 'label' => 'Keterangan Analis', 'example' => 'Pengujian dilakukan sesuai metode ISO.'],
                ]);

            case 'tagihan':
                return array_merge($common, [
                    ['key' => '{{ no_tagihan }}', 'label' => 'No. Tagihan / Inv', 'example' => 'INV-202607-001'],
                    ['key' => '{{ nama_pemohon }}', 'label' => 'Nama Pemohon', 'example' => 'Budi Santoso'],
                    ['key' => '{{ instansi }}', 'label' => 'Prodi / Instansi', 'example' => 'FMIPA UPT'],
                    ['key' => '{{ subtotal }}', 'label' => 'Subtotal Biaya (Rp)', 'example' => 'Rp 500.000'],
                    ['key' => '{{ diskon }}', 'label' => 'Nominal Diskon (Rp)', 'example' => 'Rp 50.000'],
                    ['key' => '{{ total_bayar }}', 'label' => 'Total Bayar (Rp)', 'example' => 'Rp 450.000'],
                    ['key' => '{{ status_pembayaran }}', 'label' => 'Status Pembayaran', 'example' => 'Lunas / Pending'],
                    ['key' => '{{ tanggal_pembayaran }}', 'label' => 'Tanggal Pembayaran', 'example' => '28 Juli 2026'],
                    ['key' => '{{ rincian_biaya_tabel }}', 'label' => 'Tabel Rincian Biaya (HTML)', 'example' => '[Tabel Item & Harga]'],
                ]);

            case 'sertifikat':
                return array_merge($common, [
                    ['key' => '{{ no_sertifikat }}', 'label' => 'No. Sertifikat LHU', 'example' => 'LHU/UPT-LAB/2026/001'],
                    ['key' => '{{ nama_pemohon }}', 'label' => 'Nama Pemohon / Pelanggan', 'example' => 'Budi Santoso'],
                    ['key' => '{{ instansi }}', 'label' => 'Instansi', 'example' => 'Universitas X'],
                    ['key' => '{{ judul_penelitian }}', 'label' => 'Judul Penelitian / Kegiatan', 'example' => 'Uji Mutu Air'],
                    ['key' => '{{ jenis_sampel }}', 'label' => 'Jenis / Bahan Sampel', 'example' => 'Air Limbah'],
                    ['key' => '{{ jumlah_sampel }}', 'label' => 'Jumlah Sampel', 'example' => '3 Sampel'],
                    ['key' => '{{ tanggal_selesai }}', 'label' => 'Tanggal Selesai Pengujian', 'example' => '30 Juli 2026'],
                    ['key' => '{{ tabel_hasil_uji }}', 'label' => 'Tabel Laporan Hasil Uji (HTML)', 'example' => '[Tabel Parameter, Satuan & Hasil]'],
                    ['key' => '{{ tempat_ttd_kosong }}', 'label' => 'Area Tanda Tangan Basah Kepala UPT', 'example' => '[Area TTD Kepala UPT Lab]'],
                ]);

            default:
                return $common;
        }
    }

    /**
     * Konversi file Word (.docx) ke HTML string.
     */
    public static function convertDocxToHtml(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File .docx tidak ditemukan di path: {$filePath}");
        }

        $phpWord = IOFactory::load($filePath);
        $xmlWriter = IOFactory::createWriter($phpWord, 'HTML');

        $tempFile = sys_get_temp_dir() . '/docx_' . uniqid() . '.html';
        $xmlWriter->save($tempFile);

        $html = file_get_contents($tempFile);
        @unlink($tempFile);

        // Ekstrak isi di dalam tag <body> jika ada
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            $html = $matches[1];
        }

        return trim($html);
    }

    /**
     * Generate HTML lengkap dengan styling kertas & margin untuk Dompdf.
     */
    public static function buildFullHtml(PdfTemplate $template, string $renderedBody): string
    {
        $paperSize = strtolower($template->paper_size ?? 'a4');
        $orientation = strtolower($template->orientation ?? 'portrait');
        $marginTop = $template->margin_top ?? 15;
        $marginRight = $template->margin_right ?? 15;
        $marginBottom = $template->margin_bottom ?? 15;
        $marginLeft = $template->margin_left ?? 15;

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{$template->name}</title>
    <style>
        @page {
            size: {$paperSize} {$orientation};
            margin: {$marginTop}mm {$marginRight}mm {$marginBottom}mm {$marginLeft}mm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            font-size: 10pt;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #0f172a;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mb-4 { margin-bottom: 16px; }
        .mt-4 { margin-top: 16px; }
        
        /* Kop Surat Styling */
        .kop-surat {
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }
        .kop-table {
            border: none !important;
            margin-bottom: 0 !important;
        }
        .kop-table td {
            border: none !important;
            padding: 2px !important;
        }

        /* Area Tanda Tangan */
        .ttd-box {
            width: 240px;
            float: right;
            text-align: center;
            margin-top: 30px;
        }
        .ttd-space {
            height: 75px;
        }
    </style>
</head>
<body>
    {$renderedBody}
</body>
</html>
HTML;
    }

    /**
     * Render data aktual ke dalam template HTML dan kembalikan binary PDF stream.
     */
    public static function generatePdf(PdfTemplate $template, array $data = [])
    {
        $body = $template->html_content ?? '<p>Template kosong</p>';

        // Default tanggal cetak
        $data['{{ tanggal_cetak }}'] = $data['{{ tanggal_cetak }}'] ?? date('d F Y');

        // Ganti semua placeholder {{ variable }}
        foreach ($data as $key => $value) {
            $body = str_replace($key, (string) $value, $body);
        }

        $fullHtml = self::buildFullHtml($template, $body);

        $pdf = Pdf::loadHTML($fullHtml)
            ->setPaper($template->paper_size ?? 'a4', $template->orientation ?? 'portrait');

        return $pdf;
    }
}
