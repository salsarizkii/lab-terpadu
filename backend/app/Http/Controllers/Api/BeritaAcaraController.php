<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\FormField;
use App\Models\Sample;
use Illuminate\Http\Request;

class BeritaAcaraController extends Controller
{
    public function index()
    {
        $query = BeritaAcara::with(['revisions', 'payment', 'samples', 'certificate']);

        if (!auth()->user()->hasPermission('berita_acara_riwayat_semua')) {
            $query->where('user_id', auth()->id());
        }

        return response()->json($query->get());
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
            'form_data' => 'nullable|string',
            'form_template_id' => 'nullable|integer|exists:form_templates,id',
        ]);

        $validated['user_id'] = auth()->id();

        if (isset($validated['form_data']) && is_string($validated['form_data'])) {
            $validated['form_data'] = json_decode($validated['form_data'], true);
        }

        // Extract judul_penelitian from form_data if not set directly
        if (empty($validated['judul_penelitian']) && !empty($validated['form_data']) && !empty($validated['form_template_id'])) {
            $validated['judul_penelitian'] = $this->extractJudulPenelitian($validated['form_data'], $validated['form_template_id']);
        }

        $beritaAcara = BeritaAcara::create($validated);

        return response()->json([
            'message' => 'Berita acara created successfully',
            'data' => $beritaAcara,
        ], 201);
    }

    public function show($id)
    {
        $query = BeritaAcara::with(['revisions', 'payment', 'samples', 'certificate']);

        if (!auth()->user()->hasPermission('berita_acara_riwayat_semua')) {
            $query->where('user_id', auth()->id());
        }

        return response()->json($query->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $beritaAcara = BeritaAcara::where('user_id', auth()->id())
            ->findOrFail($id);

        $isRevision = $beritaAcara->status === 'revision';

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
            'form_data' => 'nullable|string',
            'form_template_id' => 'nullable|integer|exists:form_templates,id',
        ]);

        if (isset($validated['form_data']) && is_string($validated['form_data'])) {
            $validated['form_data'] = json_decode($validated['form_data'], true);
        }

        // Extract judul_penelitian from form_data if not set directly
        if (empty($validated['judul_penelitian']) && !empty($validated['form_data'])) {
            $templateId = $validated['form_template_id'] ?? $beritaAcara->form_template_id;
            if (!empty($templateId)) {
                $validated['judul_penelitian'] = $this->extractJudulPenelitian($validated['form_data'], $templateId);
            }
        }

        // Auto-change status from revision to pending when user submits revision
        if ($isRevision) {
            $validated['status'] = 'pending';
        }

        $beritaAcara->update($validated);

        return response()->json([
            'message' => 'Berita acara updated successfully',
            'data' => $beritaAcara->fresh(),
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

    /**
     * Extract judul_penelitian value from form_data by finding the field labeled "Judul Penelitian".
     */
    private function extractJudulPenelitian(array $formData, int $templateId): ?string
    {
        $field = FormField::where('form_template_id', $templateId)
            ->where('label', 'LIKE', '%Judul Penelitian%')
            ->first();

        if ($field && isset($formData["field_{$field->id}"])) {
            return $formData["field_{$field->id}"];
        }

        return null;
    }

    public function sampleIndex()
    {
        $user = auth()->user();

        $query = Sample::with('beritaAcara.user', 'beritaAcara.analyst', 'beritaAcara.payment', 'beritaAcara.formula')
            ->whereHas('beritaAcara.payment', function ($q) {
                $q->where('status', 'approved');
            })
            ->whereHas('beritaAcara', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        return response()->json($query->get());
    }
}
