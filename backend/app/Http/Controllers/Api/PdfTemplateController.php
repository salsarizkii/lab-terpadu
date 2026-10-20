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

class PdfTemplateController extends Controller
{
    public function index()
    {
        $templates = PdfTemplate::all();
        if ($templates->isEmpty()) {
            (new PdfTemplateSeeder())->run();
            $templates = PdfTemplate::all();
        }

        return response()->json([
            'status' => 'success',
            'data' => $templates,
        ]);
    }

    public function show($type)
    {
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat'];
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
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat'];
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
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat'];
        if (!in_array($type, $validTypes)) {
            return response()->json(['message' => 'Tipe template tidak valid'], 400);
        }

        $filePath = null;

        if ($request->hasFile('docx')) {
            $request->validate([
                'docx' => 'required|file|mimes:docx|max:10240',
            ]);
            $file = $request->file('docx');
            $storedPath = $file->store('pdf_docx_templates', 'public');
            $filePath = storage_path('app/public/' . $storedPath);
        } else {
            // Gunakan file default jika tidak ada upload
            $defaultFile = base_path('../file/BERITA ACARA.docx');
            if (!file_exists($defaultFile) && $type === 'berita_acara') {
                $defaultFile = base_path('../file/Lembar kendali sampel.docx');
            }
            if (file_exists($defaultFile)) {
                $filePath = $defaultFile;
            } else {
                return response()->json(['message' => 'File .docx tidak ditemukan'], 400);
            }
        }

        try {
            $html = PdfGeneratorService::convertDocxToHtml($filePath);

            $template = PdfTemplate::where('type', $type)->first();
            if ($template) {
                $template->html_content = $html;
                if ($request->hasFile('docx')) {
                    $template->docx_path = $storedPath ?? null;
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
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat'];
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
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat'];
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
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat'];
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
        $template = PdfTemplate::where('type', $type)->where('is_active', true)->first();
        if (!$template) {
            (new PdfTemplateSeeder())->run();
            $template = PdfTemplate::where('type', $type)->first();
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
            $filename = "{$type}_{$id}.pdf";
            return response()->streamDownload(function () use ($docxPdf) {
                echo $docxPdf;
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        $pdf = PdfGeneratorService::generatePdf($template, $data);

        $filename = "{$type}_{$id}.pdf";
        return $pdf->download($filename);
    }

    /**
     * Unduh dokumen dalam bentuk Word (.docx) dengan data asli terisi.
     * Tidak membutuhkan aplikasi eksternal -- murni manipulasi file template.
     */
    public function generateDocx(Request $request, $type, $id)
    {
        $validTypes = ['berita_acara', 'rekap_analis', 'tagihan', 'sertifikat'];
        if (!in_array($type, $validTypes)) {
            return response()->json(['message' => 'Tipe template tidak valid'], 400);
        }

        $template = PdfTemplate::where('type', $type)->where('is_active', true)->first();
        if (!$template) {
            (new PdfTemplateSeeder())->run();
            $template = PdfTemplate::where('type', $type)->first();
        }

        $docx = DocxPdfService::generateFilledDocx($template, $this->getRealData($type, $id));
        if ($docx === null) {
            return response()->json([
                'message' => 'Template belum memiliki file Word. Gunakan unduhan PDF.',
            ], 422);
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
                ];
                break;

            default:
                $data = [];
        }

        // Gabungkan jawaban form BA & Rekap Analis sebagai variabel <<slug>> dinamis
        $formData = isset($ba) ? PdfGeneratorService::formDataToKeys($ba->form_data ?? [], 'berita_acara') : [];
        $rekapData = isset($ba) ? PdfGeneratorService::formDataToKeys($ba->rekap_analis_data ?? [], 'rekap_analis') : [];

        $dateData = [];
        if (isset($ba)) {
            $dateData = PdfGeneratorService::dateVariables(
                $ba,
                $payment ?? null,
                $cert ?? null
            );
        }

        return array_merge($data, $formData, $rekapData, $dateData);
    }
}
