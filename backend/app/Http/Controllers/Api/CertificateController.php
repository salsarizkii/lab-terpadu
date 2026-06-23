<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\Certificate;

class CertificateController extends Controller
{
    public function download($id)
    {
        $certificate = Certificate::findOrFail($id);
        $beritaAcara = BeritaAcara::where('user_id', auth()->id())->findOrFail($certificate->berita_acara_id);

        if (!$certificate->file_path) {
            return response()->json(['message' => 'Certificate not found'], 404);
        }

        $certificate->update(['downloaded_at' => now()]);

        $path = storage_path('app/public/' . $certificate->file_path);

        if (!file_exists($path)) {
            return response()->json(['message' => 'Certificate file not found'], 404);
        }

        return response()->download($path);
    }
}
