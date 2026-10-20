<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\Certificate;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function download($id)
    {
        $certificate = Certificate::findOrFail($id);
        $beritaAcara = BeritaAcara::query();

        // RBAC: pemilik BA atau staf dengan riwayat semua (admin/penyelia/manager)
        if (!auth()->user()->hasPermission('berita_acara_riwayat_semua')) {
            $beritaAcara->where('user_id', auth()->id());
        }

        $beritaAcara->findOrFail($certificate->berita_acara_id);

        if (!$certificate->file_path) {
            return response()->json(['message' => 'Certificate not found'], 404);
        }

        $certificate->update(['downloaded_at' => now()]);

        ActivityLogService::log($certificate->berita_acara_id, 'certificate_downloaded', 'Mengunduh sertifikat');

        $path = storage_path('app/public/' . $certificate->file_path);

        if (!file_exists($path)) {
            return response()->json(['message' => 'Certificate file not found'], 404);
        }

        return response()->download($path);
    }

    /**
     * Upload (atau ganti) sertifikat untuk berita acara yang selesai & pembayarannya disetujui.
     * POST /admin/certificates/{beritaAcara}/upload
     */
    public function upload(Request $request, $beritaAcara)
    {
        $beritaAcaraModel = BeritaAcara::with('payment')->findOrFail($beritaAcara);

        if ($beritaAcaraModel->status !== 'completed'
            || !$beritaAcaraModel->payment
            || $beritaAcaraModel->payment->status !== 'approved') {
            return response()->json(['message' => 'Sertifikat hanya bisa diupload setelah berita acara selesai dan pembayaran disetujui'], 422);
        }

        $request->validate([
            'certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $path = $request->file('certificate')->store('certificates', 'public');

        $certificate = Certificate::where('berita_acara_id', $beritaAcaraModel->id)->first();

        if ($certificate) {
            if ($certificate->file_path && Storage::disk('public')->exists($certificate->file_path)) {
                Storage::disk('public')->delete($certificate->file_path);
            }
            $certificate->update([
                'file_path' => $path,
                'downloaded_at' => null,
            ]);
        } else {
            $certificate = Certificate::create([
                'berita_acara_id' => $beritaAcaraModel->id,
                'file_path' => $path,
            ]);
        }

        ActivityLogService::log($beritaAcaraModel->id, 'certificate_uploaded', 'Mengunggah sertifikat hasil pengujian');

        return response()->json([
            'message' => 'Sertifikat berhasil diupload',
            'data' => $certificate->fresh(),
        ]);
    }

    /**
     * Hapus sertifikat yang terlanjur diupload.
     * DELETE /admin/certificates/{beritaAcara}
     */
    public function remove($beritaAcara)
    {
        $certificate = Certificate::where('berita_acara_id', $beritaAcara)->first();

        if (!$certificate) {
            return response()->json(['message' => 'Sertifikat tidak ditemukan'], 404);
        }

        if ($certificate->file_path && Storage::disk('public')->exists($certificate->file_path)) {
            Storage::disk('public')->delete($certificate->file_path);
        }

        $certificate->delete();

        ActivityLogService::log($certificate->berita_acara_id, 'certificate_removed', 'Menghapus sertifikat hasil pengujian');

        return response()->json([
            'message' => 'Sertifikat berhasil dihapus',
        ]);
    }
}
