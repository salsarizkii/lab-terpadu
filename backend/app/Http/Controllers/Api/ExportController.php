<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BeritaAcara;
use App\Models\Payment;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Support\Carbon;

class ExportController extends Controller
{
    public const SHEET_XML_HEADER = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">
XML;

    /**
     * Export seluruh data (Excel .xls multi-sheet).
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

        $xml = self::SHEET_XML_HEADER . "\n";
        $xml .= "<Styles>\n"
            . '<Style ss:ID="hdr"><Font ss:Bold="1"/><Interior ss:Color="#DDEBF7" ss:Pattern="Solid"/></Style>'
            . "\n</Styles>\n";

        foreach ($sheets as $sheet) {
            $xml .= $this->worksheet($sheet['name'], $sheet['rows']);
        }
        $xml .= "</Workbook>";

        $filename = 'export-database-' . now()->format('Ymd-His') . '.xls';

        return response($xml)
            ->header('Content-Type', 'application/vnd.ms-excel')
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
        $headers = [
            'No',
            'ID',
            'Nomor Agenda',
            'Nama Pemohon',
            'NIM',
            'Prodi/Fakultas/Instansi',
            'Email',
            'No HP',
            'Judul Penelitian',
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
                $ba->nama_pemohon ?: ($ba->user?->name ?? ''),
                $ba->nim,
                $ba->prodi_fakultas_instansi,
                $ba->email ?: ($ba->user?->email ?? ''),
                $ba->telp_hp,
                $ba->judul_penelitian,
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
                $ba->jumlah_sampel,
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
            'Nominal',
            'Ajuan Diskon',
            'Status Diskon',
            'Diskon Disetujui',
            'Tipe Diskon',
            'Diskon Persen',
            'Diskon Nominal',
            'Alasan Diskon',
            'Status',
            'Keterangan',
            'Alasan Penolakan Pembayaran',
            'Bukti Pembayaran',
            'Tanggal Upload Bukti',
            'Upload Bukti Oleh',
            'Tanggal Ajuan Diskon',
            'Ajuan Diskon Oleh',
            'Tanggal Diskon Disetujui',
            'Diskon Disetujui Oleh',
            'Tanggal Diskon Ditolak',
            'Diskon Ditolak Oleh',
            'Tanggal Diskon Diberikan',
            'Diskon Diberikan Oleh',
            'Tanggal Diskon Diubah',
            'Diskon Diubah Oleh',
            'Tanggal Diskon Dihapus',
            'Diskon Dihapus Oleh',
            'Tanggal Konfirmasi Pembayaran',
            'Konfirmasi Pembayaran Oleh',
            'Tanggal Penolakan Pembayaran',
            'Penolakan Pembayaran Oleh',
            'Dibuat',
        ]];

        $payments = Payment::with('beritaAcara.user', 'beritaAcara.activityLogs.user')->orderBy('id')->get();

        foreach ($payments as $payment) {
            $ba = $payment->beritaAcara;

            $rows[] = [
                $payment->id,
                $payment->berita_acara_id,
                $ba?->user?->name ?? ($ba?->nama_pemohon ?? ''),
                $payment->amount,
                $payment->discount_request,
                $payment->discount_status,
                $payment->discount_approved,
                $payment->discount_type,
                $payment->discount_percent,
                $payment->discount_nominal,
                $payment->discount_reason,
                $payment->status,
                $payment->keterangan,
                $payment->rejection_reason,
                $payment->payment_proof ? 'Ya' : 'Tidak',
                $this->latestLogInfo($ba, 'payment_uploaded', 'date'),
                $this->latestLogInfo($ba, 'payment_uploaded', 'name'),
                $this->latestLogInfo($ba, 'discount_requested', 'date'),
                $this->latestLogInfo($ba, 'discount_requested', 'name'),
                $this->latestLogInfo($ba, 'discount_approved', 'date'),
                $this->latestLogInfo($ba, 'discount_approved', 'name'),
                $this->latestLogInfo($ba, 'discount_rejected', 'date'),
                $this->latestLogInfo($ba, 'discount_rejected', 'name'),
                $this->latestLogInfo($ba, 'discount_given', 'date'),
                $this->latestLogInfo($ba, 'discount_given', 'name'),
                $this->latestLogInfo($ba, 'discount_updated', 'date'),
                $this->latestLogInfo($ba, 'discount_updated', 'name'),
                $this->latestLogInfo($ba, 'discount_removed', 'date'),
                $this->latestLogInfo($ba, 'discount_removed', 'name'),
                $this->latestLogInfo($ba, 'payment_confirmed', 'date'),
                $this->latestLogInfo($ba, 'payment_confirmed', 'name'),
                $this->latestLogInfo($ba, 'payment_rejected', 'date'),
                $this->latestLogInfo($ba, 'payment_rejected', 'name'),
                $this->fmt($payment->created_at),
            ];
        }

        return $rows;
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

    private function worksheet(string $name, array $rows): string
    {
        $xml = '<Worksheet ss:Name="' . $this->xmlEscape($name) . '"><Table>';

        foreach ($rows as $rowIndex => $row) {
            $xml .= '<Row>';
            foreach ($row as $cell) {
                $style = $rowIndex === 0 ? ' ss:StyleID="hdr"' : '';
                $xml .= '<Cell' . $style . '>' . $this->xmlCell($cell) . '</Cell>';
            }
            $xml .= '</Row>';
        }

        return $xml . '</Table></Worksheet>';
    }

    private function xmlCell($value): string
    {
        if ($value === null || $value === '') {
            $value = '';
        }

        $numeric = is_numeric((string) $value) && !is_string($value);

        if ($numeric) {
            return '<Data ss:Type="Number">' . $value . '</Data>';
        }

        return '<Data ss:Type="String">' . $this->xmlEscape((string) $value) . '</Data>';
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