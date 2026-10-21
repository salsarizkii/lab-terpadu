<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BeritaAcara;
use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\Payment;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Support\Carbon;

class ExportController extends Controller
{
    /**
     * Export seluruh data (Excel .xlsx multi-sheet).
     */
    public function database()
    {
        $beritaAcaras = $this->loadBeritaAcaras();

        $sheets = [
            ['name' => 'Pesanan', 'rows' => $this->pesananRows($beritaAcaras)],
            ['name' => 'Pengguna', 'rows' => $this->usersRows()],
            ['name' => 'Pembayaran', 'rows' => $this->paymentsRows()],
            ['name' => 'Sampel', 'rows' => $this->samplesRows()],
            ['name' => 'Aktivitas', 'rows' => $this->activityRows($beritaAcaras)],
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();

        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Gagal membuat file export');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels(count($sheets)));
        $zip->addFromString('xl/styles.xml', $this->styles());

        foreach ($sheets as $index => $sheet) {
            $zip->addFromString(
                'xl/worksheets/sheet' . ($index + 1) . '.xml',
                $this->worksheet($sheet['rows'])
            );
        }

        $zip->close();

        $content = file_get_contents($tmp);
        @unlink($tmp);

        $filename = 'export-database-' . now()->format('Ymd-His') . '.xlsx';

        return response($content)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Export daftar pesanan (berita acara) dalam CSV.
     */
    public function pesanan()
    {
        $beritaAcaras = $this->loadBeritaAcaras();
        $rows = $this->pesananRows($beritaAcaras);

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            $row = array_map(fn ($value) => $this->sanitizeCsv($value), $row);
            fputcsv($stream, $row, ';');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        $filename = 'export-pesanan-' . now()->format('Ymd-His') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function loadBeritaAcaras()
    {
        return BeritaAcara::with([
            'user',
            'analysts',
            'penyelias',
            'managerTeknis',
            'payment',
            'samples',
            'certificate',
            'activityLogs.user',
        ])->orderBy('created_at')->get();
    }

    /**
     * Baris sheet Pesanan: pemohon, tanggal masuk, siapa & kapan tiap verifikator memproses.
     */
    private function pesananRows($beritaAcaras): array
    {
        $dynamicFields = $this->dynamicColumns();

        $headers = [
            'No',
            'ID',
            'Nomor Agenda',
            ...array_map(fn ($field) => $field->label, $dynamicFields),
            'Tanggal Masuk',
            'Terakhir Update',
            'Status',
            'Admin Verifikator',
            'Tanggal Verifikasi Admin',
            'Penyelia',
            'Tanggal Verifikasi Penyelia',
            'Analis',
            'Tanggal Rekap Analis',
            'Manager Teknis',
            'Tanggal Verifikasi Manager',
            'Jumlah Sampel',
            'Biaya',
            'Status Pembayaran',
            'Nominal Pembayaran',
            'Diskon Disetujui',
            'Tanggal Pembayaran',
            'No Sertifikat',
            'Tanggal Sertifikat',
            'Tanggal Upload Sertifikat',
            'Tanggal Download Sertifikat',
            'Tanggal Hapus Sertifikat',
            'Tanggal Nomor Agenda Ditetapkan',
            'Tanggal Ditolak',
            'Alasan Penolakan',
            'Jumlah Revisi',
            'Tanggal Revisi Terakhir',
            'Catatan Revisi Terakhir',
            'Tanggal Dikirim Ulang',
        ];

        $rows = [$headers];
        $no = 1;

        foreach ($beritaAcaras as $ba) {
            $analysts = $ba->analysts->map(fn ($a) => $this->nameWithDate($a->name, $a->pivot->created_at ?? null))->join(', ');
            $penyelias = $ba->penyelias->map(fn ($p) => $this->nameWithDate($p->name, $p->pivot->created_at ?? null))->join(', ');
            $managers = $ba->managerTeknis->map(fn ($m) => $this->nameWithDate($m->name, $m->pivot->created_at ?? null))->join(', ');
            $revisionLogs = $ba->activityLogs->where('action', 'revision_requested')->sortByDesc('created_at');
            $latestRevision = $revisionLogs->first();

            $rows[] = [
                $no++,
                $ba->id,
                $ba->nomor_agenda,
                ...array_map(fn ($field) => $this->dynamicValue($ba, $field), $dynamicFields),
                $this->fmt($ba->created_at),
                $this->fmt($ba->updated_at),
                $ba->status,
                $this->latestLogInfo($ba, 'admin_verified', 'name'),
                $this->latestLogInfo($ba, 'admin_verified', 'date'),
                $penyelias,
                $this->latestLogInfo($ba, 'penyelia_verified', 'date'),
                $analysts,
                $this->latestLogInfo($ba, 'rekap_saved', 'date'),
                $managers,
                $this->latestLogInfo($ba, 'rekap_verified', 'date'),
                $this->formValue($ba, ['jumlah sampel', 'jumlah contoh uji'], 'jumlah_sampel'),
                $ba->biaya,
                $ba->payment?->status ?? '',
                $ba->payment?->amount ?? '',
                $ba->payment?->discount_approved ?? '',
                $this->fmt($ba->payment?->created_at),
                $ba->certificate?->certificate_number ?? '',
                $this->fmt($ba->certificate?->issued_at),
                $this->fmt($ba->certificate?->created_at),
                $this->latestLogInfo($ba, 'certificate_downloaded', 'date'),
                $this->latestLogInfo($ba, 'certificate_removed', 'date'),
                $this->latestLogInfo($ba, 'nomor_agenda', 'date'),
                $this->latestLogInfo($ba, 'rejected', 'date'),
                $ba->rejection_reason,
                $revisionLogs->count(),
                $this->fmt($latestRevision?->created_at),
                $latestRevision?->description ?? '',
                $this->latestLogInfo($ba, 'resubmitted', 'date'),
            ];
        }

        return $rows;
    }

    private function usersRows(): array
    {
        $rows = [[
            'ID',
            'Nama',
            'Email',
            'Role',
            'No HP',
            'Terverifikasi',
            'Terdaftar',
        ]];

        foreach (User::orderBy('id')->get() as $user) {
            $rows[] = [
                $user->id,
                $user->name,
                $user->email,
                $user->role,
                $user->phone,
                $user->is_verified ? 'Ya' : 'Tidak',
                $this->fmt($user->created_at),
            ];
        }

        return $rows;
    }

    private function paymentsRows(): array
    {
        $rows = [[
            'ID',
            'ID Pesanan',
            'Pemohon',
            'Nominal Total',
            'Nominal Ajuan Diskon',
            'Alasan Diskon',
            'Alasan Penolakan Pembayaran',
            'Catatan',
            'Nominal Diskon Disetujui',
            'Tipe Diskon',
            'Diskon Persen',
            'Diskon Nominal',
            'Status Diskon',
            'Status Pembayaran',
            'Bukti Pembayaran',
            'Tanggal Dibuat',
            'Tanggal Upload Bukti Pembayaran',
            'Upload Bukti Oleh',
            'Tanggal Konfirmasi Pembayaran',
            'Konfirmasi Oleh',
            'Tanggal Penolakan Pembayaran',
            'Penolakan Oleh',
            'Riwayat Diskon (JSON)',
            'Riwayat Aktivitas Pembayaran (JSON)',
        ]];

        $payments = Payment::with('beritaAcara.user', 'beritaAcara.activityLogs.user', 'discountHistories.changedBy')->orderBy('id')->get();

        foreach ($payments as $payment) {
            $ba = $payment->beritaAcara;

            $rows[] = [
                $payment->id,
                $payment->berita_acara_id,
                $ba?->user?->name ?? ($ba?->nama_pemohon ?? ''),
                $payment->amount,
                $payment->discount_request,
                $payment->discount_reason,
                $payment->rejection_reason,
                $payment->keterangan,
                $payment->discount_approved,
                $payment->discount_type,
                $payment->discount_percent,
                $payment->discount_nominal,
                $payment->discount_status,
                $payment->status,
                $payment->payment_proof ? 'Ya' : 'Tidak',
                $this->fmt($payment->created_at),
                $this->latestLogInfo($ba, 'payment_uploaded', 'date'),
                $this->latestLogInfo($ba, 'payment_uploaded', 'name'),
                $this->latestLogInfo($ba, 'payment_confirmed', 'date'),
                $this->latestLogInfo($ba, 'payment_confirmed', 'name'),
                $this->latestLogInfo($ba, 'payment_rejected', 'date'),
                $this->latestLogInfo($ba, 'payment_rejected', 'name'),
                $this->discountHistoryJson($payment),
                $this->paymentActivityJson($payment),
            ];
        }

        return $rows;
    }

    /**
     * Riwayat diskon berulang (tabel discount_histories) dijadikan satu cell JSON.
     */
    private function discountHistoryJson(Payment $payment): string
    {
        $entries = [];

        foreach ($payment->discountHistories->sortBy('created_at') as $history) {
            $entries[] = [
                'waktu' => $this->fmt($history->created_at),
                'aksi' => $history->action,
                'status' => $history->status,
                'ajuan_diskon' => $history->discount_request,
                'diskon_disetujui' => $history->discount_approved,
                'alasan' => $history->discount_reason,
                'oleh' => $history->changedBy?->name ?? '',
            ];
        }

        return $entries ? json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
    }

    /**
     * Activity log terkait pembayaran & diskon dijadikan satu cell JSON.
     */
    private function paymentActivityJson(Payment $payment): string
    {
        $ba = $payment->beritaAcara;

        if (!$ba) {
            return '';
        }

        $entries = [];

        foreach ($ba->activityLogs->sortBy('created_at') as $log) {
            if (!str_starts_with($log->action, 'payment_') && !str_starts_with($log->action, 'discount_')) {
                continue;
            }

            $entries[] = [
                'waktu' => $this->fmt($log->created_at),
                'aksi' => $log->action,
                'deskripsi' => $log->description,
                'oleh' => $log->user?->name ?? '',
            ];
        }

        return $entries ? json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
    }

    private function samplesRows(): array
    {
        $rows = [[
            'ID',
            'ID Pesanan',
            'Nama Sampel',
            'Deskripsi',
            'Jumlah',
            'Satuan',
            'Status',
            'Dibuat',
        ]];

        foreach (Sample::orderBy('id')->get() as $sample) {
            $rows[] = [
                $sample->id,
                $sample->berita_acara_id,
                $sample->name,
                $sample->description,
                $sample->quantity,
                $sample->unit,
                $sample->status,
                $this->fmt($sample->created_at),
            ];
        }

        return $rows;
    }

    private function activityRows($beritaAcaras): array
    {
        $rows = [[
            'ID Log',
            'ID Pesanan',
            'ID Revisi',
            'Waktu',
            'Aksi',
            'Deskripsi',
            'Oleh',
            'Role Pelaku',
        ]];

        foreach ($beritaAcaras as $ba) {
            foreach ($ba->activityLogs as $log) {
                $rows[] = [
                    $log->id,
                    $log->berita_acara_id,
                    $log->revision_id,
                    $this->fmt($log->created_at),
                    $log->action,
                    $log->description,
                    $log->user?->name ?? '',
                    $log->user?->role ?? '',
                ];
            }
        }

        return $rows;
    }

    /**
     * Info dari activity log terakhir untuk sebuah aksi.
     */
    private function latestLogInfo(BeritaAcara $ba, string $action, string $field): string
    {
        $log = $ba->activityLogs
            ->where('action', $action)
            ->sortByDesc('created_at')
            ->first();

        if (!$log) {
            return '';
        }

        if ($field === 'name') {
            return $log->user?->name ?? '';
        }

        return $this->fmt($log->created_at);
    }

    private function nameWithDate(?string $name, $date): string
    {
        $dateStr = $date ? $this->fmt($date) : '';
        return trim($name . ($dateStr ? " (tgl assign: {$dateStr})" : ''));
    }

    /**
     * Ambil nilai field dari form_data (template dinamis) berdasarkan keyword label,
     * fallback ke kolom lama bila tidak ada.
     */
    private function formValue(BeritaAcara $ba, array $keywords, string $fallbackColumn): string
    {
        $formData = $ba->form_data ?? [];

        if (is_array($formData) && count($formData)) {
            foreach ($this->templateFields($ba->form_template_id) as $field) {
                if (!$this->labelMatches($field->label, $keywords)) {
                    continue;
                }

                $value = $formData["field_{$field->id}"] ?? null;
                if ($value !== null && $value !== '' && !(is_array($value) && count($value) === 0)) {
                    return $this->stringifyFormValue($value);
                }
            }

            foreach ($formData as $key => $value) {
                if ($this->labelMatches($this->formatFieldKey((string) $key), $keywords)
                    && $value !== null && $value !== '') {
                    return $this->stringifyFormValue($value);
                }
            }
        }

        return (string) ($ba->{$fallbackColumn} ?? '');
    }

    private function labelMatches(string $label, array $keywords): bool
    {
        $label = mb_strtolower($label);

        foreach ($keywords as $keyword) {
            if (str_contains($label, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function formatFieldKey(string $key): string
    {
        return str_replace('_', ' ', (string) preg_replace('/^field_/', '', $key));
    }

    private function stringifyFormValue($value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => (string) ($item['label'] ?? $item), $value));
        }

        return (string) $value;
    }

    private array $templateFieldsCache = [];

    /**
     * Pertanyaan "nyala" (is_required) dari template berita_acara aktif,
     * sebagai kolom dinamis export. Diurutkan sesuai sort_order template.
     */
    private function dynamicColumns(): array
    {
        $templates = FormTemplate::where('type', 'berita_acara')
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->get();

        $seen = [];
        $fields = [];

        foreach ($templates as $template) {
            foreach ($this->templateFields($template->id) as $field) {
                if (!$field->is_required) {
                    continue;
                }

                if (in_array($field->type, ['heading', 'paragraph', 'divider', 'file'], true)) {
                    continue;
                }

                $key = $this->normalizeLabel($field->label);
                if ($key === '' || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Nilai pertanyaan dinamis untuk sebuah berita acara.
     */
    private function dynamicValue(BeritaAcara $ba, FormField $field): string
    {
        $formData = $ba->form_data ?? [];
        $normalized = $this->normalizeLabel($field->label);

        if (is_array($formData) && count($formData)) {
            foreach ($this->templateFields($ba->form_template_id) as $templateField) {
                if ($this->normalizeLabel($templateField->label) !== $normalized) {
                    continue;
                }

                $value = $formData["field_{$templateField->id}"] ?? null;
                if ($value !== null && $value !== '' && !(is_array($value) && count($value) === 0)) {
                    return $this->stringifyFormValue($value);
                }
            }

            foreach ($formData as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                if ($this->normalizeLabel($this->formatFieldKey((string) $key)) === $normalized) {
                    return $this->stringifyFormValue($value);
                }
            }
        }

        $legacy = $this->legacyColumnFor($field->label);
        if ($legacy !== null && !empty($ba->{$legacy})) {
            return (string) $ba->{$legacy};
        }

        if ($this->labelMatches($normalized, ['nama pemohon'])) {
            return $ba->user?->name ?? '';
        }

        if ($this->labelMatches($normalized, ['email'])) {
            return $ba->user?->email ?? '';
        }

        return '';
    }

    /**
     * Petakan label pertanyaan ke kolom lama berita_acaras bila ada.
     */
    private function legacyColumnFor(string $label): ?string
    {
        $map = [
            [['nama pemohon'], 'nama_pemohon'],
            [['nim'], 'nim'],
            [['prodi', 'fakultas', 'instansi'], 'prodi_fakultas_instansi'],
            [['dosen'], 'dosen_pembimbing'],
            [['telp', 'no. hp'], 'telp_hp'],
            [['email'], 'email'],
            [['alamat'], 'alamat'],
            [['judul'], 'judul_penelitian'],
            [['akses lab'], 'akses_lab'],
            [['bahan'], 'bahan_contoh_uji'],
            [['kemasan', 'packing'], 'kemasan_packing'],
            [['permintaan', 'metode', 'instrumen'], 'permintaan_analisa'],
            [['jumlah sampel', 'jumlah contoh uji'], 'jumlah_sampel'],
            [['kode'], 'kode_nama_sampel'],
            [['keterangan sampel'], 'keterangan_sampel'],
            [['keterangan tambahan'], 'keterangan_tambahan'],
            [['pengukuran', 'pengulangan'], 'pengukuran_per_sampel'],
            [['sisa'], 'sisa_sampel'],
            [['masa berlaku'], 'masa_berlaku_akses'],
        ];

        foreach ($map as [$keywords, $column]) {
            if ($this->labelMatches($label, $keywords)) {
                return $column;
            }
        }

        return null;
    }

    private function normalizeLabel(string $label): string
    {
        $normalized = trim((string) preg_replace('/[^a-z0-9]+/u', ' ', mb_strtolower(trim($label))));

        return trim((string) preg_replace('/\s+/', ' ', $normalized));
    }

    /**
     * @return \Illuminate\Support\Collection<int, FormField>
     */
    private function templateFields(?int $templateId)
    {
        if ($templateId === null) {
            return collect();
        }

        if (!isset($this->templateFieldsCache[$templateId])) {
            $this->templateFieldsCache[$templateId] = FormField::where('form_template_id', $templateId)
                ->orderBy('sort_order')
                ->get();
        }

        return $this->templateFieldsCache[$templateId];
    }

    private function fmt($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function contentTypes(int $sheetCount): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';

        for ($i = 1; $i <= $sheetCount; $i++) {
            $xml .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return $xml . '</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbook(array $sheets): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>';

        foreach ($sheets as $index => $sheet) {
            $xml .= '<sheet name="' . $this->xmlEscape($sheet['name']) . '" sheetId="' . ($index + 1) . '" r:id="rId' . ($index + 1) . '"/>';
        }

        return $xml . '</sheets></workbook>';
    }

    private function workbookRels(int $sheetCount): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        for ($i = 1; $i <= $sheetCount; $i++) {
            $xml .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }

        $xml .= '<Relationship Id="rId' . ($sheetCount + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return $xml . '</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFDDEBF7"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function worksheet(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $xml .= '<row r="' . ($rowIndex + 1) . '">';

            foreach (array_values($row) as $colIndex => $cell) {
                if ($cell === null || $cell === '') {
                    continue;
                }

                $ref = $this->columnLetter($colIndex + 1) . ($rowIndex + 1);
                $style = $rowIndex === 0 ? ' s="1"' : '';

                if (is_bool($cell)) {
                    $cell = $cell ? 1 : 0;
                }

                if (!is_string($cell) && is_numeric($cell)) {
                    $xml .= '<c r="' . $ref . '"' . $style . '><v>' . $cell . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">' . $this->xmlEscape((string) $cell) . '</t></is></c>';
                }
            }

            $xml .= '</row>';
        }

        return $xml . '</sheetData></worksheet>';
    }

    private function columnLetter(int $number): string
    {
        $letters = '';

        while ($number > 0) {
            $number--;
            $letters = chr(65 + ($number % 26)) . $letters;
            $number = intdiv($number, 26);
        }

        return $letters;
    }

    private function xmlEscape(string $value): string
    {
        $value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value);

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function sanitizeCsv($value): string
    {
        $value = $value === null ? '' : (string) $value;

        if (preg_match('/^[=+\-@]/', $value)) {
            $value = "'" . $value;
        }

        return $value;
    }
}