<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\Certificate;
use App\Models\Payment;
use App\Models\PdfTemplate;
use App\Services\DocxPdfService;
use App\Services\PdfGeneratorService;
use App\Services\SystemActivityLogService;
use Database\Seeders\PdfTemplateSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfTemplateController extends Controller
{
    public function index()
    {
        $templates = PdfTemplate::orderByDesc('created_at')->get();
        if ($templates->isEmpty()) {
            (new PdfTemplateSeeder())->run();
            $templates = PdfTemplate::orderByDesc('created_at')->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $templates,
        ]);
    }

    public function show($type)
    {
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat', 'pengambilan_sisa_sampel', 'lembar_kendali_sampel'];
        if (!in_array($type, $validTypes)) {
            return response()->json(['message' => 'Tipe template tidak valid'], 400);
        }

        $template = PdfTemplate::where('type', $type)->first();
        if (!$template) {
            (new PdfTemplateSeeder())->run();
            $template = PdfTemplate::where('type', $type)->first();
        }

        $variables = PdfGeneratorService::getAvailableVariables($type);

        return response()->json([
            'status' => 'success',
            'data' => $template,
            'available_variables' => $variables,
        ]);
    }

    public function update(Request $request, $type)
    {
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat', 'pengambilan_sisa_sampel', 'lembar_kendali_sampel'];
        if (!in_array($type, $validTypes)) {
            return response()->json(['message' => 'Tipe template tidak valid'], 400);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'html_content' => 'sometimes|string',
            'paper_size' => 'sometimes|string|in:a4,letter,legal',
            'orientation' => 'sometimes|string|in:portrait,landscape',
            'margin_top' => 'sometimes|integer|min:0|max:100',
            'margin_bottom' => 'sometimes|integer|min:0|max:100',
            'margin_left' => 'sometimes|integer|min:0|max:100',
            'margin_right' => 'sometimes|integer|min:0|max:100',
            'is_active' => 'sometimes|boolean',
            'show_kop' => 'sometimes|boolean',
            'show_ttd' => 'sometimes|boolean',
            'show_rincian' => 'sometimes|boolean',
            'catatan' => 'nullable|string|max:5000',
            'settings' => 'sometimes|array',
        ]);

        $template = PdfTemplate::updateOrCreate(
            ['type' => $type],
            array_merge($validated, ['created_by' => auth()->id()])
        );

        SystemActivityLogService::log(
            'pdf_template_updated',
            "Memperbarui template PDF: {$type}",
            PdfTemplate::class,
            $template->id,
            null,
            ['type' => $type]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Template PDF berhasil diperbarui',
            'data' => $template,
        ]);
    }

    public function importDocx(Request $request, $type)
    {
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat', 'pengambilan_sisa_sampel', 'lembar_kendali_sampel'];
        if (!in_array($type, $validTypes)) {
            return response()->json(['message' => 'Tipe template tidak valid'], 400);
        }

        $filePath = null;
        $storedPath = null;

        if ($request->hasFile('docx')) {
            $request->validate([
                'docx' => 'required|file|mimes:docx|max:10240',
            ]);
            $file = $request->file('docx');
            $storedPath = $file->store('pdf_docx_templates', 'public');
            $filePath = storage_path('app/public/' . $storedPath);
        } else {
            // Ambil file template default dari backend/file
            $defaults = [
                'berita_acara' => base_path('file/berita_acara_revised.docx'),
                'sertifikat' => base_path('file/SHP.docx'),
                'pengambilan_sisa_sampel' => base_path('file/PENGAMBILAN SISA SAMPELBAHAN.docx'),
                'lembar_kendali_sampel' => base_path('file/Lembar kendali sampel.docx'),
            ];
            $defaultFile = $defaults[$type] ?? null;

            // Fallback lama: Lembar kendali sampel untuk berita acara
            if (($defaultFile === null || !file_exists($defaultFile)) && $type === 'berita_acara') {
                $fallback = base_path('file/Lembar kendali sampel.docx');
                $defaultFile = file_exists($fallback) ? $fallback : null;
            }

            if ($defaultFile === null || !file_exists($defaultFile)) {
                return response()->json(['message' => 'File .docx default tidak ditemukan di backend/file'], 400);
            }

            // Salin ke storage agar pipeline docx (docx_path) ikut terpakai
            $storedPath = 'pdf_docx_templates/' . Str::random(40) . '.docx';
            if (!copy($defaultFile, storage_path('app/public/' . $storedPath))) {
                return response()->json(['message' => 'Gagal menyalin file default ke storage'], 500);
            }
            $filePath = storage_path('app/public/' . $storedPath);
        }

        try {
            $html = PdfGeneratorService::convertDocxToHtml($filePath);

            $template = PdfTemplate::where('type', $type)->first();
            if ($template) {
                $template->html_content = $html;
                if ($storedPath) {
                    $template->docx_path = $storedPath;
                }
                $template->save();
            }

            SystemActivityLogService::log(
                'pdf_template_imported_docx',
                "Mengimpor DOCX ke template PDF: {$type}",
                PdfTemplate::class,
                $template->id ?? null,
                null,
                ['type' => $type]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengimpor dokumen Word (.docx) ke HTML',
                'html_content' => $html,
                'data' => $template,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengimpor file Word: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unduh template saat ini dalam bentuk Word (.docx).
     * Jika ada file asli yang di-upload → kirim file tersebut; jika tidak → generate dari HTML.
     */
    public function downloadDocx($type)
    {
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat', 'pengambilan_sisa_sampel', 'lembar_kendali_sampel'];
        if (!in_array($type, $validTypes)) {
            return response()->json(['message' => 'Tipe template tidak valid'], 400);
        }

        $template = PdfTemplate::where('type', $type)->first();
        if (!$template) {
            (new PdfTemplateSeeder())->run();
            $template = PdfTemplate::where('type', $type)->first();
        }

        $filename = 'template_' . $type . '.docx';

        if ($template->docx_path) {
            $path = storage_path('app/public/' . $template->docx_path);
            if (file_exists($path)) {
                return response()->download($path, $filename);
            }
        }

        $content = PdfGeneratorService::generateDocxFromHtml($template->html_content ?? '');

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function preview(Request $request, $type)
    {
        $template = PdfTemplate::where('type', $type)->first();
        if (!$template) {
            $template = new PdfTemplate(['type' => $type, 'name' => 'Preview Template']);
        }

        // Terapkan override dari request jika user sedang mengedit di frontend
        $this->applyPreviewOverrides($request, $template);

        $dummyData = $this->getDummyData($type);

        // Prioritas: render dari file Word asli (hasil persis seperti template Word).
        try {
            $docxPdf = DocxPdfService::generatePdf($template, $dummyData);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal membuat PDF dari template Word: ' . $e->getMessage(),
            ], 500);
        }
        if ($docxPdf !== null) {
            return response()->streamDownload(function () use ($docxPdf) {
                echo $docxPdf;
            }, "preview_{$type}.pdf", [
                'Content-Type' => 'application/pdf',
            ]);
        }

        $pdf = PdfGeneratorService::generatePdf($template, $dummyData);

        return $pdf->stream("preview_{$type}.pdf");
    }

    /**
     * Preview Word: .docx hasil isi data contoh (dummy). Tanpa konversi PDF.
     */
    public function previewDocx(Request $request, $type)
    {
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat', 'pengambilan_sisa_sampel', 'lembar_kendali_sampel'];
        if (!in_array($type, $validTypes)) {
            return response()->json(['message' => 'Tipe template tidak valid'], 400);
        }

        $template = PdfTemplate::where('type', $type)->first();
        if (!$template) {
            (new PdfTemplateSeeder())->run();
            $template = PdfTemplate::where('type', $type)->first();
        }

        $this->applyPreviewOverrides($request, $template);

        $docx = DocxPdfService::generateFilledDocx($template, $this->getDummyData($type));
        if ($docx === null) {
            return response()->json([
                'message' => 'Template belum memiliki file Word. Upload file .docx terlebih dahulu untuk pratinjau Word.',
            ], 422);
        }

        return response()->streamDownload(function () use ($docx) {
            echo $docx;
        }, "preview_{$type}.docx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Terapkan pengaturan dari request ke template (untuk preview tanpa simpan).
     */
    private function applyPreviewOverrides(Request $request, PdfTemplate $template): void
    {
        $fields = [
            'html_content', 'paper_size', 'orientation', 'catatan',
        ];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $template->{$field} = $request->input($field);
            }
        }

        $intFields = ['margin_top', 'margin_bottom', 'margin_left', 'margin_right'];
        foreach ($intFields as $field) {
            if ($request->has($field)) {
                $template->{$field} = (int)$request->input($field);
            }
        }

        $boolFields = ['show_kop', 'show_ttd', 'show_rincian'];
        foreach ($boolFields as $field) {
            if ($request->has($field)) {
                $template->{$field} = filter_var($request->input($field), FILTER_VALIDATE_BOOLEAN);
            }
        }

        if ($request->has('settings') && is_array($request->input('settings'))) {
            $template->settings = $request->input('settings');
        }
    }

    /**
     * Kembalikan template ke versi awal (seeder) untuk satu tipe dokumen.
     */
    public function reset($type)
    {
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat', 'pengambilan_sisa_sampel', 'lembar_kendali_sampel'];
        if (!in_array($type, $validTypes)) {
            return response()->json(['message' => 'Tipe template tidak valid'], 400);
        }

        (new PdfTemplateSeeder())->run();

        $template = PdfTemplate::where('type', $type)->first();

        SystemActivityLogService::log(
            'pdf_template_reset',
            "Meriset template PDF ke versi awal: {$type}",
            PdfTemplate::class,
            $template->id ?? null,
            null,
            ['type' => $type]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Template PDF dikembalikan ke versi awal',
            'data' => $template,
        ]);
    }

    public function generate(Request $request, $type, $id)
    {
        $this->authorizeGenerate($type, $id);

        $template = PdfTemplate::where('type', $type)->where('is_active', true)->first();
        if (!$template) {
            (new PdfTemplateSeeder())->run();
            $template = PdfTemplate::where('type', $type)->first();
        }

        // Cache: dokumen yang sama (template & data tidak berubah) langsung
        // dikirim tanpa render ulang agar download cepat.
        $cachePath = $this->cachePath($type, (int) $id, $template, 'pdf');
        if ($cachePath && is_file($cachePath) && filesize($cachePath) > 0) {
            $filename = "{$type}_{$id}.pdf";
            return response()->streamDownload(function () use ($cachePath) {
                echo (string) file_get_contents($cachePath);
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        $data = $this->getRealData($type, $id);

        // Prioritas: render dari file Word asli (hasil persis seperti template Word).
        try {
            $docxPdf = DocxPdfService::generatePdf($template, $data);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal membuat PDF dari template Word: ' . $e->getMessage(),
            ], 500);
        }
        if ($docxPdf !== null) {
            if ($cachePath) {
                @file_put_contents($cachePath, $docxPdf);
            }
            $filename = "{$type}_{$id}.pdf";
            return response()->streamDownload(function () use ($docxPdf) {
                echo $docxPdf;
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        $pdf = PdfGeneratorService::generatePdf($template, $data);

        if ($cachePath) {
            @file_put_contents($cachePath, (string) $pdf->output());
        }

        $filename = "{$type}_{$id}.pdf";
        return $pdf->download($filename);
    }

    /**
     * Path file cache untuk dokumen yang sudah pernah digenerate.
     * Key = hash dari tipe, id, versi template, dan tanggal update entitas terkait,
     * sehingga perubahan data otomatis membatalkan cache.
     */
    private function cachePath(string $type, int $id, ?PdfTemplate $template, string $format): ?string
    {
        if (!$template) {
            return null;
        }

        $time = $template->updated_at ? (string) $template->updated_at : (string) $template->created_at;

        switch ($type) {
            case 'tagihan':
                $p = Payment::find($id);
                $entityTime = $p?->updated_at ? (string) $p->updated_at : '';
                break;
            case 'sertifikat':
                $c = Certificate::find($id);
                $entityTime = $c?->updated_at ? (string) $c->updated_at : '';
                break;
            default:
                $ba = BeritaAcara::find($id);
                $entityTime = $ba?->updated_at ? (string) $ba->updated_at : '';
                break;
        }

        $key = md5("{$type}|{$id}|{$template->id}|{$time}|{$entityTime}");

        $dir = storage_path('app/pdf-cache');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir . DIRECTORY_SEPARATOR . $key . '.' . $format;
    }

    /**
     * Unduh dokumen dalam bentuk Word (.docx) dengan data asli terisi.
     * Tidak membutuhkan aplikasi eksternal -- murni manipulasi file template.
     */
    public function generateDocx(Request $request, $type, $id)
    {
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat', 'pengambilan_sisa_sampel', 'lembar_kendali_sampel'];
        if (!in_array($type, $validTypes)) {
            return response()->json(['message' => 'Tipe template tidak valid'], 400);
        }

        $this->authorizeGenerate($type, $id);

        $template = PdfTemplate::where('type', $type)->where('is_active', true)->first();
        if (!$template) {
            (new PdfTemplateSeeder())->run();
            $template = PdfTemplate::where('type', $type)->first();
        }

        // Cache Word agar download berikutnya langsung jadi.
        $cachePath = $this->cachePath($type, (int) $id, $template, 'docx');
        if ($cachePath && is_file($cachePath) && filesize($cachePath) > 0) {
            $filename = "{$type}_{$id}.docx";
            return response()->streamDownload(function () use ($cachePath) {
                echo (string) file_get_contents($cachePath);
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        }

        $docx = DocxPdfService::generateFilledDocx($template, $this->getRealData($type, $id));
        if ($docx === null) {
            // Fallback: template tanpa file Word → generate .docx dari render HTML.
            $body = PdfGeneratorService::renderBody($template, $this->getRealData($type, $id));
            $docx = PdfGeneratorService::generateDocxFromHtml($body);
            if ($docx === null || $docx === '') {
                return response()->json([
                    'message' => 'Gagal membuat dokumen Word dari template.',
                ], 422);
            }
        }

        if ($cachePath) {
            @file_put_contents($cachePath, $docx);
        }

        $filename = "{$type}_{$id}.docx";
        return response()->streamDownload(function () use ($docx) {
            echo $docx;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    private function getDummyData($type): array
    {
        $sampleTableHtml = '<table><thead><tr><th>No</th><th>Kode Hasil Pengujian</th><th>Parameter Uji</th><th>Jumlah</th></tr></thead><tbody><tr><td>1</td><td>HP-001</td><td>Kadar Air, pH, Logam Berat</td><td>100 ml</td></tr><tr><td>2</td><td>HP-002</td><td>Viskositas & Kejernihan</td><td>250 gr</td></tr></tbody></table>';

        switch ($type) {
            case 'berita_acara':
                $data = [
                    '{{ agenda }}' => 'AGD-2026-0006',
                    '{{ id }}' => 'BA-2026-001',
                    '{{ nama_pemohon }}' => 'Budi Santoso, S.Si',
                    '{{ nim }}' => '200101099',
                    '{{ prodi_fakultas_instansi }}' => 'Teknik Kimia / Fakultas Teknik',
                    '{{ dosen_pembimbing }}' => 'Dr. Ir. Hendra, M.T.',
                    '{{ telp_hp }}' => '081234567890',
                    '{{ email }}' => 'budi.santoso@example.com',
                    '{{ alamat }}' => 'Jl. Pendidikan No. 45, Bandar Lampung',
                    '{{ judul_penelitian }}' => 'Analisis Karakteristik Senyawa Aktif Ekstrak Daun',
                    '{{ bahan_contoh_uji }}' => 'Ekstrak Cairan Herbal',
                    '{{ jumlah_sampel }}' => '2 Sampel',
                    '{{ status_pelanggan }}' => 'Mahasiswa Internal',
                    '{{ status_berkas }}' => 'Disetujui',
                    '{{ tanggal_pengajuan }}' => date('d F Y'),
                    '{{ total_biaya }}' => 'Rp 750.000',
                    '{{ tabel_sampel }}' => $sampleTableHtml,
                ];
                break;

            case 'rekap_analis':
                $data = [
                    '{{ agenda }}' => 'AGD-2026-0006',
                    '{{ id }}' => 'BA-2026-001',
                    '{{ nama_pemohon }}' => 'Budi Santoso, S.Si',
                    '{{ instansi }}' => 'Teknik Kimia / Universitas X',
                    '{{ nama_analyst }}' => 'Ahmad Razi, M.Sc (Analis Utama)',
                    '{{ status }}' => 'Pengujian Selesai',
                    '{{ tanggal_pengajuan }}' => date('d F Y'),
                    '{{ tabel_pengujian }}' => '<table><thead><tr><th>Parameter</th><th>Metode</th><th>Satuan</th><th>Hasil</th></tr></thead><tbody><tr><td>pH</td><td>pH Meter ISO 10523</td><td>-</td><td>7.2</td></tr><tr><td>Kadar Air</td><td>Gravimetri</td><td>%</td><td>4.5 %</td></tr></tbody></table>',
                    '{{ keterangan_analis }}' => 'Semua hasil pengujian memenuhi batas kriteria pengujian baku mutu.',
                ];
                break;

            case 'tagihan':
                $data = [
                    '{{ agenda }}' => 'AGD-2026-0006',
                    '{{ no_tagihan }}' => 'INV/2026/07/0042',
                    '{{ nama_pemohon }}' => 'Budi Santoso, S.Si',
                    '{{ instansi }}' => 'Fakultas Teknik',
                    '{{ subtotal }}' => 'Rp 750.000',
                    '{{ diskon }}' => 'Rp 50.000',
                    '{{ total_bayar }}' => 'Rp 700.000',
                    '{{ status_pembayaran }}' => 'LUNAS',
                    '{{ tanggal_pembayaran }}' => date('d F Y'),
                    '{{ rincian_biaya_tabel }}' => '<table><thead><tr><th>Deskripsi Pengujian</th><th>Jumlah</th><th>Harga Satuan</th><th>Total</th></tr></thead><tbody><tr><td>Uji pH & Viskositas</td><td>2 Hasil Pengujian</td><td>Rp 250.000</td><td>Rp 500.000</td></tr><tr><td>Uji Kadar Air</td><td>2 Hasil Pengujian</td><td>Rp 125.000</td><td>Rp 250.000</td></tr></tbody></table>',
                ];
                break;

            case 'sertifikat':
                $data = [
                    '{{ agenda }}' => 'AGD-2026-0006',
                    '{{ no_sertifikat }}' => 'LHU/UPT-LAB/2026/07-001',
                    '{{ nama_pemohon }}' => 'Budi Santoso, S.Si',
                    '{{ instansi }}' => 'Fakultas Teknik Universitas X',
                    '{{ judul_penelitian }}' => 'Analisis Karakteristik Senyawa Ekstrak',
                    '{{ jenis_sampel }}' => 'Ekstrak Cairan Herbal',
                    '{{ jumlah_sampel }}' => '2 Sampel',
                    '{{ tanggal_selesai }}' => date('d F Y'),
                    '{{ tabel_hasil_uji }}' => '<table><thead><tr><th>No</th><th>Parameter Uji</th><th>Satuan</th><th>Baku Mutu</th><th>Hasil Uji</th></tr></thead><tbody><tr><td>1</td><td>Derajat Keasaman (pH)</td><td>-</td><td>6.5 - 8.5</td><td>7.20</td></tr><tr><td>2</td><td>Kadar Air</td><td>% (b/b)</td><td>Maks 10.0</td><td>4.50</td></tr></tbody></table>',
                ];
                break;

            case 'lembar_kendali_sampel':
                $data = [
                    '{{ agenda }}' => 'AGD-2026-0006',
                    '{{ id }}' => 'BA-2026-001',
                    '{{ nama_pemohon }}' => 'Budi Santoso, S.Si',
                    '{{ kode_nama_sampel }}' => 'HP-001',
                    '{{ metode_instrumen }}' => 'PSA Particle Size',
                    '{{ jumlah_sampel_contoh_uji }}' => '2',
                    '{{ pengukuran_pengulangan_per_sampel }}' => '3x',
                ];
                break;

            case 'pengambilan_sisa_sampel':
                $data = [
                    '{{ agenda }}' => 'AGD-2026-0006',
                    '{{ nomor_form }}' => 'PSS-2026-001',
                    '{{ id }}' => 'BA-2026-001',
                    '{{ nama_pemohon }}' => 'Budi Santoso, S.Si',
                    '{{ nim }}' => '200101099',
                    '{{ instansi }}' => 'Teknik Kimia / Fakultas Teknik',
                    '{{ judul_penelitian }}' => 'Analisis Karakteristik Senyawa Aktif Ekstrak Daun',
                    '{{ jenis_sampel }}' => 'Ekstrak Cairan Herbal',
                    '{{ jumlah_sampel }}' => '2 Sampel',
                    '{{ sisa_sampel }}' => '± 150 ml',
                    '{{ tanggal_pengajuan }}' => date('d F Y'),
                    '{{ tanggal_pengambilan }}' => date('d F Y'),
                    '{{ tabel_sisa_sampel }}' => '<table><thead><tr><th>No</th><th>Kode Hasil Pengujian</th><th>Nama Sisa Sampel/Bahan</th><th>Jumlah Diambil</th></tr></thead><tbody><tr><td>1</td><td>HP-001</td><td>Ekstrak Cairan Herbal</td><td>± 100 ml</td></tr><tr><td>2</td><td>HP-002</td><td>Serbuk Daun Kering</td><td>± 50 gr</td></tr></tbody></table>',
                ];
                break;

            default:
                $data = [];
        }

        // Gabungkan contoh jawaban dari pertanyaan form BA & Rekap Analis
        return array_merge(
            $data,
            PdfGeneratorService::formFieldDummyValues('berita_acara'),
            PdfGeneratorService::formFieldDummyValues('rekap_analis'),
            PdfGeneratorService::dateDummyValues()
        );
    }

    /**
     * RBAC: hanya pemilik dokumen, analis yang ditugaskan, atau staf dengan
     * permission riwayat semua yang boleh generate PDF/DOCX.
     */
    private function authorizeGenerate(string $type, $id): void
    {
        $ba = null;

        switch ($type) {
            case 'tagihan':
                $payment = Payment::with('beritaAcara')->findOrFail($id);
                $ba = $payment->beritaAcara;
                break;
            case 'sertifikat':
                $certificate = Certificate::with('beritaAcara')->findOrFail($id);
                $ba = $certificate->beritaAcara;
                break;
            default:
                $ba = BeritaAcara::findOrFail($id);
        }

        if (!$ba) {
            abort(404);
        }

        $user = auth()->user();

        if ($ba->user_id === $user->id) {
            return;
        }

        if ($user->hasPermission('berita_acara_riwayat_semua')) {
            return;
        }

        if ($ba->analysts()->where('users.id', $user->id)->exists()) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke dokumen ini');
    }

    private function getRealData($type, $id): array
    {
        switch ($type) {
            case 'berita_acara':
                $ba = BeritaAcara::with(['user', 'samples'])->findOrFail($id);
                $samplesHtml = '<table><thead><tr><th>No</th><th>Kode Hasil Pengujian</th><th>Keterangan</th></tr></thead><tbody>';
                if ($ba->samples && count($ba->samples) > 0) {
                    foreach ($ba->samples as $idx => $s) {
                        $num = $idx + 1;
                        $samplesHtml .= "<tr><td>{$num}</td><td>" . e($s->sample_code) . "</td><td>" . e($s->notes ?? '-') . "</td></tr>";
                    }
                } else {
                    $samplesHtml .= "<tr><td colspan='3'>" . e($ba->kode_nama_sampel ?? 'Tidak ada data sampel') . "</td></tr>";
                }
                $samplesHtml .= '</tbody></table>';

                $data = [
                    '{{ agenda }}' => $ba->nomor_agenda ?? '-',
                    '{{ id }}' => 'BA-' . str_pad($ba->id, 4, '0', STR_PAD_LEFT),
                    '{{ nama_pemohon }}' => $ba->nama_pemohon ?? $ba->user?->name ?? '-',
                    '{{ nim }}' => $ba->nim ?? '-',
                    '{{ prodi_fakultas_instansi }}' => $ba->prodi_fakultas_instansi ?? '-',
                    '{{ dosen_pembimbing }}' => $ba->dosen_pembimbing ?? '-',
                    '{{ telp_hp }}' => $ba->telp_hp ?? '-',
                    '{{ email }}' => $ba->email ?? $ba->user?->email ?? '-',
                    '{{ alamat }}' => $ba->alamat ?? '-',
                    '{{ judul_penelitian }}' => $ba->judul_penelitian ?? '-',
                    '{{ bahan_contoh_uji }}' => $ba->bahan_contoh_uji ?? '-',
                    '{{ jumlah_sampel }}' => $ba->jumlah_sampel ?? '-',
                    '{{ status_pelanggan }}' => $ba->status_pelanggan ?? '-',
                    '{{ status_berkas }}' => ucfirst($ba->status),
                    '{{ tanggal_pengajuan }}' => $ba->created_at ? $ba->created_at->format('d F Y') : '-',
                    '{{ total_biaya }}' => $ba->biaya ? 'Rp ' . number_format((float)$ba->biaya, 0, ',', '.') : 'Rp 0',
                    '{{ tabel_sampel }}' => $samplesHtml,
                    // Alias slug form template → kolom berita_acaras (untuk template Word ber-slug form)
                    '{{ telp_no_hp }}' => $ba->telp_hp ?? '-',
                    '{{ judul_tema_penelitian }}' => $ba->judul_penelitian ?? '-',
                    '{{ bahan_contoh_uji_material }}' => $ba->bahan_contoh_uji ?? '-',
                    '{{ jumlah_sampel_contoh_uji }}' => $ba->jumlah_sampel ?? '-',
                    '{{ kode_nama_sampel }}' => $ba->kode_nama_sampel ?? '-',
                    '{{ keterangan_sampel }}' => $ba->keterangan_sampel ?? '-',
                    '{{ keterangan_tambahan }}' => $ba->keterangan_tambahan ?? '-',
                    '{{ pengukuran_pengulangan_per_sampel }}' => $ba->pengukuran_per_sampel ?? '-',
                    '{{ kemasan_packing }}' => $ba->kemasan_packing ?? '-',
                    '{{ akses_lab_di_upt_lab_terpadu }}' => $ba->akses_lab ?? '-',
                    '{{ masa_berlaku_akses }}' => $ba->masa_berlaku_akses ?? '-',
                    '{{ permintaan_analisa_instrumentasi_metode }}' => $ba->permintaan_analisa ?? '-',
                ];
                break;

            case 'tagihan':
                $payment = Payment::with(['beritaAcara.user'])->findOrFail($id);
                $ba = $payment->beritaAcara;

                $rincianTabel = '<table><thead><tr><th>Deskripsi</th><th>Total</th></tr></thead><tbody>';
                $rincianTabel .= "<tr><td>Biaya Pengujian (" . e($ba->bahan_contoh_uji ?? 'Layanan Lab') . ")</td><td>Rp " . number_format((float)$payment->amount, 0, ',', '.') . "</td></tr>";
                $rincianTabel .= '</tbody></table>';

                $subtotal = (float)$payment->amount;
                $diskon = (float)($payment->discount_approved ?? 0);
                $totalBayar = max(0, $subtotal - $diskon);

                $data = [
                    '{{ agenda }}' => $ba->nomor_agenda ?? '-',
                    '{{ no_tagihan }}' => 'INV-' . date('Ym') . '-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT),
                    '{{ nama_pemohon }}' => $ba->nama_pemohon ?? $ba->user?->name ?? '-',
                    '{{ instansi }}' => $ba->prodi_fakultas_instansi ?? '-',
                    '{{ subtotal }}' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
                    '{{ diskon }}' => 'Rp ' . number_format($diskon, 0, ',', '.'),
                    '{{ total_bayar }}' => 'Rp ' . number_format($totalBayar, 0, ',', '.'),
                    '{{ status_pembayaran }}' => strtoupper($payment->status ?? 'PENDING'),
                    '{{ tanggal_pembayaran }}' => $payment->updated_at ? $payment->updated_at->format('d F Y') : '-',
                    '{{ rincian_biaya_tabel }}' => $rincianTabel,
                ];
                break;

            case 'rekap_analis':
                $ba = BeritaAcara::with(['analyst', 'user'])->findOrFail($id);
                $rekapData = $ba->rekap_analis_data ?? [];

                $tabelHasil = '<table><thead><tr><th>Parameter / Item</th><th>Keterangan / Hasil</th></tr></thead><tbody>';
                if (is_array($rekapData) && count($rekapData) > 0) {
                    foreach ($rekapData as $k => $v) {
                        $tabelHasil .= "<tr><td>" . e($k) . "</td><td>" . e(is_array($v) ? json_encode($v) : $v) . "</td></tr>";
                    }
                } else {
                    $tabelHasil .= "<tr><td colspan='2'>Belum ada rincian data hasil pengujian</td></tr>";
                }
                $tabelHasil .= '</tbody></table>';

                $data = [
                    '{{ agenda }}' => $ba->nomor_agenda ?? '-',
                    '{{ id }}' => 'BA-' . str_pad($ba->id, 4, '0', STR_PAD_LEFT),
                    '{{ nama_pemohon }}' => $ba->nama_pemohon ?? $ba->user?->name ?? '-',
                    '{{ instansi }}' => $ba->prodi_fakultas_instansi ?? '-',
                    '{{ nama_analyst }}' => $ba->analyst?->name ?? 'Tim Analis UPT',
                    '{{ status }}' => ucfirst($ba->status),
                    '{{ tanggal_pengajuan }}' => $ba->created_at ? $ba->created_at->format('d F Y') : '-',
                    '{{ tabel_pengujian }}' => $tabelHasil,
                    '{{ keterangan_analis }}' => $ba->keterangan ?? 'Pengujian telah diselesaikan sesuai standar operasional laboratorium.',
                ];
                break;

            case 'sertifikat':
                $cert = Certificate::with(['beritaAcara.user'])->findOrFail($id);
                $ba = $cert->beritaAcara;

                $tabelHasil = '<table><thead><tr><th>Parameter</th><th>Hasil Uji</th></tr></thead><tbody>';
                $rekapData = $ba->rekap_analis_data ?? [];
                if (is_array($rekapData) && count($rekapData) > 0) {
                    foreach ($rekapData as $k => $v) {
                        $tabelHasil .= "<tr><td>" . e($k) . "</td><td>" . e(is_array($v) ? json_encode($v) : $v) . "</td></tr>";
                    }
                } else {
                    $tabelHasil .= "<tr><td colspan='2'>Memenuhi Spesifikasi Lab</td></tr>";
                }
                $tabelHasil .= '</tbody></table>';

                $data = [
                    '{{ agenda }}' => $ba->nomor_agenda ?? '-',
                    '{{ no_sertifikat }}' => $cert->certificate_number ?? ('LHU/' . date('Y') . '/' . str_pad($cert->id, 4, '0', STR_PAD_LEFT)),
                    '{{ nama_pemohon }}' => $ba->nama_pemohon ?? $ba->user?->name ?? '-',
                    '{{ instansi }}' => $ba->prodi_fakultas_instansi ?? '-',
                    '{{ judul_penelitian }}' => $ba->judul_penelitian ?? '-',
                    '{{ jenis_sampel }}' => $ba->bahan_contoh_uji ?? '-',
                    '{{ jumlah_sampel }}' => $ba->jumlah_sampel ?? '-',
                    '{{ tanggal_selesai }}' => $cert->issued_at ? date('d F Y', strtotime($cert->issued_at)) : date('d F Y'),
                    '{{ tabel_hasil_uji }}' => $tabelHasil,
                    // Alias slug form template → kolom berita_acaras
                    '{{ kode_nama_sampel }}' => $ba->kode_nama_sampel ?? '-',
                    '{{ kemasan_packing }}' => $ba->kemasan_packing ?? '-',
                    '{{ prodi_fakultas_instansi }}' => $ba->prodi_fakultas_instansi ?? '-',
                    // Diisi jawaban form "Metode/Instrumen" bila ada; fallback '-'
                    '{{ metode_instrumen }}' => '-',
                ];
                break;

            case 'lembar_kendali_sampel':
                $ba = BeritaAcara::with(['user', 'analysts'])->findOrFail($id);
                $data = [
                    '{{ agenda }}' => $ba->nomor_agenda ?? '-',
                    '{{ id }}' => 'BA-' . str_pad($ba->id, 4, '0', STR_PAD_LEFT),
                    '{{ nama_pemohon }}' => $ba->nama_pemohon ?? $ba->user?->name ?? '-',
                    '{{ kode_nama_sampel }}' => $ba->kode_nama_sampel ?? '-',
                    '{{ jumlah_sampel_contoh_uji }}' => $ba->jumlah_sampel ?? '-',
                    '{{ pengukuran_pengulangan_per_sampel }}' => $ba->pengukuran_per_sampel ?? '-',
                    // Diisi jawaban form "Metode/Instrumen" bila ada; fallback '-'
                    '{{ metode_instrumen }}' => '-',
                    '{{ nama_analyst }}' => $ba->analysts->pluck('name')->filter()->implode(', ')
                        ?: ($ba->analyst?->name ?? '-'),
                ];
                break;

            case 'pengambilan_sisa_sampel':
                $ba = BeritaAcara::with(['user', 'samples'])->findOrFail($id);

                $sisaHtml = '<table><thead><tr><th>No</th><th>Kode Hasil Pengujian</th><th>Nama Sisa Sampel/Bahan</th><th>Jumlah Diambil</th></tr></thead><tbody>';
                if ($ba->samples && count($ba->samples) > 0) {
                    foreach ($ba->samples as $idx => $s) {
                        $num = $idx + 1;
                        $nama = $s->name ?? $ba->kode_nama_sampel ?? '-';
                        $jumlah = $s->quantity ? $s->quantity . ($s->unit ? ' ' . $s->unit : '') : '-';
                        $sisaHtml .= "<tr><td>{$num}</td><td>" . e($s->sample_code ?? '-') . "</td><td>" . e($nama) . "</td><td>" . e($jumlah) . "</td></tr>";
                    }
                } else {
                    $sisaHtml .= "<tr><td colspan='4'>" . e($ba->kode_nama_sampel ?? 'Tidak ada data sampel') . "</td></tr>";
                }
                $sisaHtml .= '</tbody></table>';

                $data = [
                    '{{ agenda }}' => $ba->nomor_agenda ?? '-',
                    '{{ nomor_form }}' => 'PSS-' . str_pad($ba->id, 4, '0', STR_PAD_LEFT),
                    '{{ id }}' => 'BA-' . str_pad($ba->id, 4, '0', STR_PAD_LEFT),
                    '{{ nama_pemohon }}' => $ba->nama_pemohon ?? $ba->user?->name ?? '-',
                    '{{ nim }}' => $ba->nim ?? '-',
                    '{{ instansi }}' => $ba->prodi_fakultas_instansi ?? '-',
                    '{{ judul_penelitian }}' => $ba->judul_penelitian ?? '-',
                    '{{ jenis_sampel }}' => $ba->bahan_contoh_uji ?? '-',
                    '{{ jumlah_sampel }}' => $ba->jumlah_sampel ?? '-',
                    '{{ sisa_sampel }}' => $ba->sisa_sampel ?? '-',
                    '{{ tanggal_pengajuan }}' => $ba->created_at ? $ba->created_at->format('d F Y') : '-',
                    '{{ tanggal_pengambilan }}' => date('d F Y'),
                    '{{ tabel_sisa_sampel }}' => $sisaHtml,
                    // Alias slug form template → kolom berita_acaras
                    '{{ telp_no_hp }}' => $ba->telp_hp ?? '-',
                    '{{ judul_tema_penelitian }}' => $ba->judul_penelitian ?? '-',
                    '{{ akses_lab_di_upt_lab_terpadu }}' => $ba->akses_lab ?? '-',
                    '{{ bahan_contoh_uji_material }}' => $ba->bahan_contoh_uji ?? '-',
                    '{{ kemasan_packing }}' => $ba->kemasan_packing ?? '-',
                    '{{ kode_nama_sampel }}' => $ba->kode_nama_sampel ?? '-',
                    '{{ keterangan_sampel }}' => $ba->keterangan_sampel ?? '-',
                    '{{ jumlah_sampel_contoh_uji }}' => $ba->jumlah_sampel ?? '-',
                    '{{ permintaan_analisa_instrumentasi_metode }}' => $ba->permintaan_analisa ?? '-',
                    '{{ pengukuran_pengulangan_per_sampel }}' => $ba->pengukuran_per_sampel ?? '-',
                    '{{ prodi_fakultas_instansi }}' => $ba->prodi_fakultas_instansi ?? '-',
                    '{{ dosen_pembimbing }}' => $ba->dosen_pembimbing ?? '-',
                    '{{ email }}' => $ba->email ?? $ba->user?->email ?? '-',
                    '{{ alamat }}' => $ba->alamat ?? '-',
                    '{{ status_pelanggan }}' => $ba->status_pelanggan ?? '-',
                ];
                break;

            default:
                $data = [];
        }

        // Gabungkan jawaban form BA & Rekap Analis sebagai variabel <<slug>> dinamis
        $formData = isset($ba) ? PdfGeneratorService::formDataToKeys($ba->form_data ?? [], 'berita_acara') : [];
        $rekapData = isset($ba) ? PdfGeneratorService::formDataToKeys($ba->rekap_analis_data ?? [], 'rekap_analis') : [];

        // Petugas yang mengurus BA ini: admin/penyelia/manager teknis aktual.
        // Kalau belum diverifikasi/terverifikasi, tampilkan "Belum diverifikasi".
        // Relasi & log verifikasi dimuat sekali, dipakai bersama oleh petugas & tanggal.
        $petugasData = [];
        $dateData = [];
        if (isset($ba)) {
            $ba->loadMissing(['revisions', 'pengujians', 'samples', 'penyelias', 'managerTeknis']);
            $verifyLogs = $ba->activityLogs()
                ->whereIn('action', ['admin_verified', 'penyelia_verified', 'rekap_verified'])
                ->with('user:id,name,role')
                ->latest()
                ->get();

            $petugasData = self::petugasVariables($ba, $verifyLogs);
            $dateData = PdfGeneratorService::dateVariables(
                $ba,
                $payment ?? null,
                $cert ?? null,
                $verifyLogs
            );
        }

        return array_merge($data, $formData, $rekapData, $dateData, $petugasData);
    }

    /**
     * Nama petugas yang menangani sebuah berita acara:
     * - Admin: user yang melakukan verifikasi admin (log `admin_verified`)
     * - Penyelia: user yang memverifikasi (log `penyelia_verified`), fallback ke yang di-assign
     * - Manager Teknis: user yang memverifikasi (log `rekap_verified`), fallback ke yang di-assign
     * Bila belum ada → "Belum diverifikasi".
     * Override superadmin tidak dipakai sebagai nama petugas: pakai petugas asli
     * yang ditugaskan, dan hanya fallback ke verifikator bila tidak ada penugasan.
     * $verifyLogs: activityLogs verifikasi yang sudah dimuat sekali oleh pemanggil.
     */
    private function petugasVariables(BeritaAcara $ba, ?\Illuminate\Support\Collection $verifyLogs = null): array
    {
        $belum = 'Belum diverifikasi';

        if ($verifyLogs === null) {
            $verifyLogs = $ba->activityLogs()
                ->whereIn('action', ['admin_verified', 'penyelia_verified', 'rekap_verified'])
                ->with('user:id,name,role')
                ->latest()
                ->get();
        }

        $adminLog = $verifyLogs->firstWhere('action', 'admin_verified');
        $penyeliaLog = $verifyLogs->firstWhere('action', 'penyelia_verified');
        $managerLog = $verifyLogs->firstWhere('action', 'rekap_verified');

        $adminName = $adminLog?->user?->name;

        $penyeliaVerifier = $penyeliaLog?->user;
        $managerVerifier = $managerLog?->user;

        $penyeliaName = ($penyeliaVerifier && $penyeliaVerifier->role !== 'superadmin' ? $penyeliaVerifier->name : null)
            ?? $ba->penyelias->pluck('name')->filter()->first()
            ?? $penyeliaVerifier?->name;
        $managerName = ($managerVerifier && $managerVerifier->role !== 'superadmin' ? $managerVerifier->name : null)
            ?? $ba->managerTeknis->pluck('name')->filter()->first()
            ?? $managerVerifier?->name;

        return [
            '{{ nama_admin }}' => $adminName ?: $belum,
            '{{ nama_penyelia }}' => $penyeliaName ?: $belum,
            '{{ nama_manager_teknis }}' => $managerName ?: $belum,
        ];
    }
}
