<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use Illuminate\Http\Request;

class BeritaAcaraController extends Controller
{
    public function index()
    {
        $beritaAcaras = BeritaAcara::where('user_id', auth()->id())
            ->with(['revisions', 'payment', 'samples', 'certificate'])
            ->get();

        return response()->json($beritaAcaras);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_pemohon' => 'nullable|string',
            'nim' => 'nullable|string',
            'prodi_fakultas_instansi' => 'nullable|string',
            'dosen_pembimbing' => 'nullable|string',
            'telp_hp' => 'nullable|string',
            'email' => 'nullable|string',
            'alamat' => 'nullable|string',
            'judul_penelitian' => 'nullable|string',
            'akses_lab' => 'nullable|string',
            'bahan_contoh_uji' => 'nullable|string',
            'kemasan_packing' => 'nullable|string',
            'permintaan_analisa' => 'nullable|string',
            'jumlah_sampel' => 'nullable|string',
            'kode_nama_sampel' => 'nullable|string',
            'keterangan_sampel' => 'nullable|string',
            'keterangan_tambahan' => 'nullable|string',
            'pengukuran_per_sampel' => 'nullable|string',
            'sisa_sampel' => 'nullable|string',
            'upload_akses' => 'nullable|string',
            'masa_berlaku_akses' => 'nullable|string',
            'status_pelanggan' => 'nullable|string',
            'status_berkas' => 'nullable|string',
            'status' => 'nullable|string|in:draft,pending',
        ]);

        $validated['user_id'] = auth()->id();

        $beritaAcara = BeritaAcara::create($validated);

        return response()->json([
            'message' => 'Berita acara created successfully',
            'data' => $beritaAcara,
        ], 201);
    }

    public function show($id)
    {
        $beritaAcara = BeritaAcara::where('user_id', auth()->id())
            ->with(['revisions', 'payment', 'samples', 'certificate'])
            ->findOrFail($id);

        return response()->json($beritaAcara);
    }

    public function update(Request $request, $id)
    {
        $beritaAcara = BeritaAcara::where('user_id', auth()->id())
            ->findOrFail($id);

        if (!in_array($beritaAcara->status, ['draft', 'pending', 'revision'])) {
            return response()->json([
                'message' => 'Cannot update berita acara with status: ' . $beritaAcara->status,
            ], 422);
        }

        $validated = $request->validate([
            'nama_pemohon' => 'nullable|string',
            'nim' => 'nullable|string',
            'prodi_fakultas_instansi' => 'nullable|string',
            'dosen_pembimbing' => 'nullable|string',
            'telp_hp' => 'nullable|string',
            'email' => 'nullable|string',
            'alamat' => 'nullable|string',
            'judul_penelitian' => 'nullable|string',
            'akses_lab' => 'nullable|string',
            'bahan_contoh_uji' => 'nullable|string',
            'kemasan_packing' => 'nullable|string',
            'permintaan_analisa' => 'nullable|string',
            'jumlah_sampel' => 'nullable|string',
            'kode_nama_sampel' => 'nullable|string',
            'keterangan_sampel' => 'nullable|string',
            'keterangan_tambahan' => 'nullable|string',
            'pengukuran_per_sampel' => 'nullable|string',
            'sisa_sampel' => 'nullable|string',
            'upload_akses' => 'nullable|string',
            'masa_berlaku_akses' => 'nullable|string',
            'status_pelanggan' => 'nullable|string',
            'status_berkas' => 'nullable|string',
        ]);

        $beritaAcara->update($validated);

        return response()->json([
            'message' => 'Berita acara updated successfully',
            'data' => $beritaAcara,
        ]);
    }

    public function destroy($id)
    {
        $beritaAcara = BeritaAcara::where('user_id', auth()->id())
            ->findOrFail($id);

        if ($beritaAcara->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft can be deleted',
            ], 422);
        }

        $beritaAcara->delete();

        return response()->json(['message' => 'Berita acara deleted successfully']);
    }
}
