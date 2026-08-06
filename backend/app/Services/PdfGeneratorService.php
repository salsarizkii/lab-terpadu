<?php

namespace App\Services;

use App\Models\BeritaAcara;
use App\Models\Certificate;
use App\Models\DiscountHistory;
use App\Models\FormTemplate;
use App\Models\Payment;
use App\Models\PdfTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;

class PdfGeneratorService
{
    /**
     * Dapatkan daftar variabel dinamis berkelompok untuk semua jenis dokumen.
     * Sumber variabel: pertanyaan form (BA & Rekap Analis), data pembayaran, dan data dokumen.
     */
    public static function getAvailableVariables(string $type): array
    {
        $groups = [
            [
                'group' => 'Pertanyaan Form Berita Acara',
                'variables' => self::formFieldVariables('berita_acara'),
            ],
            [
                'group' => 'Pertanyaan Form Rekap Analis',
                'variables' => self::formFieldVariables('rekap_analis'),
            ],
            [
                'group' => 'Pembayaran & Diskon',
                'variables' => [
                    ['key' => '{{ no_tagihan }}', 'label' => 'No. Tagihan / Invoice', 'example' => 'INV-202607-001'],
                    ['key' => '{{ subtotal }}', 'label' => 'Subtotal Biaya (Rp)', 'example' => 'Rp 500.000'],
                    ['key' => '{{ diskon }}', 'label' => 'Nominal Diskon (Rp)', 'example' => 'Rp 50.000'],
                    ['key' => '{{ total_bayar }}', 'label' => 'Total Yang Harus Dibayar (Rp)', 'example' => 'Rp 450.000'],
                    ['key' => '{{ status_pembayaran }}', 'label' => 'Status Pembayaran', 'example' => 'Lunas / Pending'],
                    ['key' => '{{ tanggal_pembayaran }}', 'label' => 'Tanggal Bayar / Pembayaran Dikonfirmasi', 'example' => '28 Juli 2026'],
                    ['key' => '{{ rincian_biaya_tabel }}', 'label' => 'Tabel Rincian Pembayaran (HTML)', 'example' => '[Tabel Item & Harga]'],
                ],
            ],
            [
                'group' => 'Tanggal-Tanggal',
                'variables' => [
                    ['key' => '{{ tanggal_berita_acara }}', 'label' => 'Tanggal Berita Acara Dibuat', 'example' => '25 Juli 2026'],
                    ['key' => '{{ tanggal_verifikasi }}', 'label' => 'Tanggal Diverifikasi / Disetujui', 'example' => '26 Juli 2026'],
                    ['key' => '{{ tanggal_revisi }}', 'label' => 'Tanggal Revisi Terakhir', 'example' => '27 Juli 2026'],
                    ['key' => '{{ tanggal_rekap_analis }}', 'label' => 'Tanggal Rekap Analisis Disimpan', 'example' => '29 Juli 2026'],
                    ['key' => '{{ tanggal_pembayaran }}', 'label' => 'Tanggal Bayar / Pembayaran Dikonfirmasi', 'example' => '29 Juli 2026'],
                    ['key' => '{{ tanggal_diskon }}', 'label' => 'Tanggal Diskon Diberikan', 'example' => '30 Juli 2026'],
                    ['key' => '{{ tanggal_sampel_diambil }}', 'label' => 'Tanggal Sampel Diambil', 'example' => '31 Juli 2026'],
                    ['key' => '{{ tanggal_sertifikat }}', 'label' => 'Tanggal Sertifikat Terbit', 'example' => '1 Agustus 2026'],
                ],
            ],
            [
                'group' => 'Data Dokumen',
                'variables' => self::documentDataVariables($type),
            ],
        ];

        // Buang duplikasi key antar grup (kelompok belakangan menang)
        $seen = [];
        foreach ($groups as &$group) {
            $group['variables'] = array_values(array_filter($group['variables'], function ($v) use (&$seen) {
                $key = $v['key'];
                if (isset($seen[$key])) {
                    return false;
                }
                $seen[$key] = true;
                return true;
            }));
        }
        unset($group);

        return array_values(array_filter($groups, fn($g) => count($g['variables']) > 0));
    }

    /**
     * Variabel dari pertanyaan form template (berita_acara / rekap_analis).
     * Key = slug label pertanyaan, contoh: "Nama Pemohon" → <<nama_pemohon>>.
     */
    public static function formFieldVariables(string $formType): array
    {
        $fields = self::loadFormFields($formType);
        if (empty($fields)) {
            return [];
        }

        $result = [];
        foreach (self::buildSlugMap($fields) as $fieldId => $slug) {
            $field = $fields[$fieldId];
            $result[] = [
                'key' => '{{ ' . $slug . ' }}',
                'label' => 'Jawaban "' . $field['label'] . '"',
                'example' => 'Jawaban yang diisi user di form',
            ];
        }

        return $result;
    }

    /**
     * Ambil field dari form template aktif sesuai tipe form.
     * Return: array field_id => ['id' =>, 'label' =>, 'type' =>].
     */
    private static function loadFormFields(string $formType): array
    {
        $template = FormTemplate::with('fields')
            ->where('type', $formType)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return [];
        }

        $layoutTypes = ['heading', 'paragraph', 'divider'];
        $fields = [];
        foreach ($template->fields as $field) {
            if (in_array($field->type, $layoutTypes, true)) {
                continue;
            }
            $fields['field_' . $field->id] = [
                'id' => $field->id,
                'label' => $field->label,
                'type' => $field->type,
            ];
        }

        return $fields;
    }

    /**
     * Buat pemetaan field_{id} → slug unik dari label.
     * Label sama → diberi suffix _2, _3. Label tak bisa di-slug → field_{id}.
     */
    public static function buildSlugMap(array $fields): array
    {
        $used = [];
        $map = [];
        foreach ($fields as $fieldId => $field) {
            // Ganti garis miring dengan spasi agar slug rapi (Str::slug membuang '/').
            $base = Str::slug(str_replace(['/', '\\'], ' ', $field['label']), '_');
            if ($base === '') {
                $base = $fieldId;
            }
            $slug = $base;
            $i = 2;
            while (isset($used[$slug])) {
                $slug = $base . '_' . $i++;
            }
            $used[$slug] = true;
            $map[$fieldId] = $slug;
        }

        return $map;
    }

    /**
     * Ubah data jawaban form (key field_{id}) menjadi pasangan key {{ slug }} → nilai.
     */
    public static function formDataToKeys(array $formData, string $formType): array
    {
        $fields = self::loadFormFields($formType);
        if (empty($fields)) {
            return [];
        }

        $map = self::buildSlugMap($fields);
        $out = [];
        foreach ($formData as $key => $value) {
            $slug = $map[$key] ?? null;
            if (!$slug) {
                continue;
            }
            if (is_array($value)) {
                $value = implode(', ', array_filter($value, fn($v) => $v !== '' && $v !== null));
            }
            if (is_scalar($value)) {
                $out['{{ ' . $slug . ' }}'] = (string) $value;
            }
        }

        return $out;
    }

    /**
     * Nilai dummy untuk preview dari setiap pertanyaan form.
     */
    public static function formFieldDummyValues(string $formType): array
    {
        $fields = self::loadFormFields($formType);
        if (empty($fields)) {
            return [];
        }

        $map = self::buildSlugMap($fields);
        $out = [];
        foreach ($map as $fieldId => $slug) {
            $out['{{ ' . $slug . ' }}'] = 'Contoh jawaban "' . $fields[$fieldId]['label'] . '"';
        }

        return $out;
    }

    /**
     * Nilai semua variabel tanggal dari data berita acara & relasinya.
     */
    public static function dateVariables(BeritaAcara $ba, ?Payment $payment = null, ?Certificate $certificate = null): array
    {
        $fmt = fn($date) => $date ? date('d F Y', strtotime((string) $date)) : null;

        $pengujian = $ba->pengujians()->latest('tanggal_pengujian')->first();
        $lastRevision = $ba->revisions()->latest()->first();
        $lastDiscount = $payment ? DiscountHistory::where('payment_id', $payment->id)->latest()->first() : null;
        $lastTakenSample = $ba->samples()->where('status', 'taken')->latest('updated_at')->value('updated_at');

        $values = [
            '{{ tanggal_berita_acara }}' => $ba->created_at,
            // Alias backward-compat: template lama memakai {{ tanggal_pengajuan }}
            '{{ tanggal_pengajuan }}' => $ba->created_at,
            '{{ tanggal_verifikasi }}' => $ba->status === 'approved' ? $ba->updated_at : null,
            '{{ tanggal_revisi }}' => $lastRevision?->created_at,
            '{{ tanggal_rekap_analis }}' => $ba->rekap_analis_data ? $ba->updated_at : null,
            '{{ tanggal_pembayaran }}' => $payment?->updated_at,
            '{{ tanggal_diskon }}' => $lastDiscount?->created_at ?? $payment?->updated_at,
            '{{ tanggal_sampel_diambil }}' => $lastTakenSample,
            '{{ tanggal_sertifikat }}' => $certificate?->created_at ?? $certificate?->downloaded_at,
        ];

        $out = [];
        foreach ($values as $key => $date) {
            $out[$key] = $fmt($date) ?? '-';
        }

        return $out;
    }

    /**
     * Nilai dummy tanggal untuk preview.
     */
    public static function dateDummyValues(): array
    {
        $keys = [
            '{{ tanggal_berita_acara }}',
            '{{ tanggal_pengajuan }}',
            '{{ tanggal_verifikasi }}',
            '{{ tanggal_revisi }}',
            '{{ tanggal_rekap_analis }}',
            '{{ tanggal_pembayaran }}',
            '{{ tanggal_diskon }}',
            '{{ tanggal_sampel_diambil }}',
            '{{ tanggal_sertifikat }}',
        ];

        $today = date('d F Y');
        return array_fill_keys($keys, $today);
    }

    /**
     * Variabel data dokumen per jenis: identitas lab + nilai komputasi spesifik tipe.
     */
    private static function documentDataVariables(string $type): array
    {
        $common = [
            ['key' => '{{ agenda }}', 'label' => 'Nomor Agenda / Surat', 'example' => 'AGD-2026-0006'],
            ['key' => '{{ id }}', 'label' => 'ID Berita Acara', 'example' => 'BA-2026-001'],
            ['key' => '{{ kop_nama_lab }}', 'label' => 'Nama Instansi / Laboratorium', 'example' => 'UPT LABORATORIUM TERPADU'],
            ['key' => '{{ kop_alamat }}', 'label' => 'Alamat Lab', 'example' => 'Jl. Prof. Dr. Sumantri Brojonegoro No. 1'],
            ['key' => '{{ kop_email }}', 'label' => 'Email Lab', 'example' => 'labterpadu@univ.ac.id'],
            ['key' => '{{ kop_telepon }}', 'label' => 'Telepon Lab', 'example' => '(0721) 123456'],
            ['key' => '{{ kepala_upt }}', 'label' => 'Nama Kepala UPT', 'example' => 'Prof. Dr. Kepala UPT, M.Si'],
            ['key' => '{{ nip_kepala_upt }}', 'label' => 'NIP Kepala UPT', 'example' => '197501012000121001'],
            ['key' => '{{ nama_admin }}', 'label' => 'Nama Admin / Petugas Penerima', 'example' => 'Admin UPT Lab Terpadu'],
            ['key' => '{{ nama_bendahara }}', 'label' => 'Nama Bendahara / Petugas Keuangan', 'example' => 'Petugas Keuangan'],
        ];

        switch ($type) {
            case 'berita_acara':
                return array_merge($common, [
                    ['key' => '{{ status_berkas }}', 'label' => 'Status Berkas', 'example' => 'Disetujui'],
                    ['key' => '{{ total_biaya }}', 'label' => 'Total Biaya (Rp)', 'example' => 'Rp 500.000'],
                    ['key' => '{{ tabel_sampel }}', 'label' => 'Tabel Daftar Hasil Pengujian (HTML)', 'example' => '[Tabel Hasil Pengujian & Parameter]'],
                ]);

            case 'rekap_analis':
                return array_merge($common, [
                    ['key' => '{{ nama_analyst }}', 'label' => 'Nama Analis / Tim Analis', 'example' => 'Dr. Rina, M.Sc'],
                    ['key' => '{{ status }}', 'label' => 'Status Pengujian', 'example' => 'Selesai Analisis'],
                    ['key' => '{{ tabel_pengujian }}', 'label' => 'Tabel Hasil Pengujian Analis (HTML)', 'example' => '[Tabel Parameter & Nilai Hasil]'],
                    ['key' => '{{ keterangan_analis }}', 'label' => 'Keterangan Analis', 'example' => 'Pengujian sesuai metode ISO.'],
                ]);

            case 'sertifikat':
                return array_merge($common, [
                    ['key' => '{{ no_sertifikat }}', 'label' => 'No. Sertifikat LHU', 'example' => 'LHU/UPT-LAB/2026/001'],
                    ['key' => '{{ tabel_hasil_uji }}', 'label' => 'Tabel Laporan Hasil Uji (HTML)', 'example' => '[Tabel Parameter, Satuan & Hasil]'],
                    ['key' => '{{ tempat_ttd_kosong }}', 'label' => 'Area Tanda Tangan Basah Kepala UPT', 'example' => '[Area TTD Kepala UPT Lab]'],
                ]);

            default:
                return $common;
        }
    }

    /**
     * Hapus blok bagian dokumen berdasarkan marker komentar.
     * Marker: <!-- KOP_START --> ... <!-- KOP_END -->, TTD, RINCIAN.
     */
    public static function stripSection(string $html, string $section): string
    {
        $pattern = '/<!--\s*' . $section . '_START\s*-->.*?<!--\s*' . $section . '_END\s*-->/is';
        return preg_replace($pattern, '', $html) ?? $html;
    }

    /**
     * Nilai default identitas & tanda tangan untuk form pengaturan.
     */
    public static function defaultSettings(): array
    {
        return [
            'kop_nama_lab' => 'UPT LABORATORIUM TERPADU',
            'kop_alamat' => 'Jl. Prof. Dr. Sumantri Brojonegoro No. 1, Bandar Lampung',
            'kop_email' => 'labterpadu@univ.ac.id',
            'kop_telepon' => '(0721) 123456',
            'kepala_upt' => 'Prof. Dr. Kepala UPT, M.Si',
            'nip_kepala_upt' => '197501012000121001',
            'nama_admin' => 'Admin UPT Lab Terpadu',
            'nama_bendahara' => 'Petugas Keuangan',
        ];
    }

    /**
     * Gabungkan data identitas dari settings template ke dalam data render,
     * lalu isi variabel {{ kop_* }}, {{ kepala_upt }}, dst.
     */
    public static function applySettings(PdfTemplate $template, array &$data): void
    {
        $settings = array_merge(self::defaultSettings(), (array)($template->settings ?? []));

        $map = [
            '{{ kop_nama_lab }}' => $settings['kop_nama_lab'],
            '{{ kop_alamat }}' => $settings['kop_alamat'],
            '{{ kop_email }}' => $settings['kop_email'],
            '{{ kop_telepon }}' => $settings['kop_telepon'],
            '{{ kepala_upt }}' => $settings['kepala_upt'],
            '{{ nip_kepala_upt }}' => $settings['nip_kepala_upt'],
            '{{ nama_admin }}' => $settings['nama_admin'],
            '{{ nama_bendahara }}' => $settings['nama_bendahara'],
        ];

        foreach ($map as $key => $value) {
            if (!isset($data[$key])) {
                $data[$key] = $value;
            }
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
        self::applySettings($template, $data);

        $data['{{ tanggal_cetak }}'] = $data['{{ tanggal_cetak }}'] ?? date('d F Y');

        $body = $template->html_content ?? '<p>Template kosong</p>';

        $body = self::normalizePlaceholders($body);

        $body = self::stripSectionsByToggles($template, $body);

        foreach ($data as $key => $value) {
            $body = str_replace($key, (string) $value, $body);
        }

        if ($template->catatan) {
            $body .= '<div style="margin-top:20px;font-size:10pt;">' . nl2br(e($template->catatan)) . '</div>';
        }

        $fullHtml = self::buildFullHtml($template, $body);

        $pdf = Pdf::loadHTML($fullHtml)
            ->setPaper($template->paper_size ?? 'a4', $template->orientation ?? 'portrait');

        return $pdf;
    }

    /**
     * Normalisasi semua format placeholder ke {{ nama }}.
     * Mendukung: <<nama>>, ${nama}, {{ nama }}, dan versi entity (&lt;&lt;nama&gt;&gt;).
     */
    public static function normalizePlaceholders(string $body): string
    {
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = preg_replace('/<<([\w\-]+)>>/', '{{ $1 }}', $body) ?? $body;
        $body = preg_replace('/\$\{([\w\-]+)\}/', '{{ $1 }}', $body) ?? $body;
        $body = preg_replace('/\{\{\s*([\w\-]+)\s*\}\}/', '{{ $1 }}', $body) ?? $body;

        return $body;
    }

    /**
     * Generate file .docx dari HTML template.
     * Placeholder {{ nama }} diubah menjadi <<nama>> agar sesuai panduan pengguna Word.
     * Mengembalikan konten biner docx.
     */
    public static function generateDocxFromHtml(string $html): string
    {
        // {{ nama }} → <<nama>>, lalu escape agar terbaca sebagai teks oleh parser HTML
        $html = preg_replace('/\{\{\s*([\w\-]+)\s*\}\}/', '&lt;&lt;$1&gt;&gt;', $html) ?? $html;

        $tempHtml = sys_get_temp_dir() . '/docxsrc_' . uniqid() . '.html';
        $fullHtml = '<html><head><meta charset="utf-8" /></head><body>' . $html . '</body></html>';
        file_put_contents($tempHtml, $fullHtml);

        try {
            $phpWord = IOFactory::load($tempHtml, 'HTML');
        } catch (\Exception $e) {
            Log::warning('PhpWord HTML reader gagal, fallback ke teks biasa: ' . $e->getMessage());
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();
            $section->addText(trim(strip_tags(html_entity_decode($html))));
        } finally {
            @unlink($tempHtml);
        }

        $tempDocx = sys_get_temp_dir() . '/docxout_' . uniqid() . '.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempDocx);

        // PhpWord tidak meng-escape teks <<nama>>, >>, dan & pada document.xml sehingga XML-nya invalid.
        // Escape manual agar file bisa dibuka di Word & dibaca ulang oleh PhpWord.
        $zip = new \ZipArchive();
        if ($zip->open($tempDocx) === true) {
            $documentXml = $zip->getFromName('word/document.xml');
            if ($documentXml !== false) {
                $documentXml = str_replace(['<<', '>>'], ['&lt;&lt;', '&gt;&gt;'], $documentXml);
                // Escape & yang belum berupa entity
                $documentXml = preg_replace('/&(?![a-zA-Z0-9#]+;)/', '&amp;', $documentXml) ?? $documentXml;
                $zip->addFromString('word/document.xml', $documentXml);
            }
            $zip->close();
        }

        $content = file_get_contents($tempDocx);
        @unlink($tempDocx);

        return $content;
    }

    /**
     * Hapus bagian kop / TTD / rincian sesuai toggle template.
     */
    private static function stripSectionsByToggles(PdfTemplate $template, string $body): string
    {
        if (!$template->show_kop) {
            $body = self::stripSection($body, 'KOP');
        }
        if (!$template->show_ttd) {
            $body = self::stripSection($body, 'TTD');
        }
        if (!$template->show_rincian) {
            $body = self::stripSection($body, 'RINCIAN');
        }

        return $body;
    }
}
