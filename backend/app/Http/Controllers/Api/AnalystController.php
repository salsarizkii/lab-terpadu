<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\Pengujian;
use App\Models\Sample;
use Illuminate\Http\Request;

class AnalystController extends Controller
{
    public function beritaAcaraIndex()
    {
        $beritaAcaras = BeritaAcara::whereHas('analysts', function ($q) {
                $q->where('users.id', auth()->id());
            })
            ->with(['user', 'analysts', 'revisions', 'payment', 'samples', 'certificate', 'pengujians'])
            ->get();

        return response()->json($beritaAcaras);
    }

    public function beritaAcaraShow($id)
    {
        $beritaAcara = BeritaAcara::whereHas('analysts', function ($q) {
                $q->where('users.id', auth()->id());
            })
            ->with(['user', 'analysts', 'revisions', 'payment', 'samples', 'certificate', 'pengujians'])
            ->findOrFail($id);

        return response()->json($beritaAcara);
    }

    public function updateSample(Request $request, $id, $sampleId)
    {
        $beritaAcara = BeritaAcara::whereHas('analysts', function ($q) {
                $q->where('users.id', auth()->id());
            })->findOrFail($id);

        $sample = Sample::where('berita_acara_id', $beritaAcara->id)
            ->findOrFail($sampleId);

        $request->validate([
            'name' => 'nullable|string',
            'description' => 'nullable|string',
            'quantity' => 'nullable|string',
            'unit' => 'nullable|string',
            'test_result' => 'nullable|string',
            'status' => 'nullable|string|in:pending,processing,ready,taken',
        ]);

        $sample->update($request->only([
            'name', 'description', 'quantity', 'unit', 'test_result', 'status'
        ]));

        return response()->json([
            'message' => 'Sample updated successfully',
            'data' => $sample,
        ]);
    }

    public function markSampleReady($id, $sampleId)
    {
        $beritaAcara = BeritaAcara::whereHas('analysts', function ($q) {
                $q->where('users.id', auth()->id());
            })->findOrFail($id);

        $sample = Sample::where('berita_acara_id', $beritaAcara->id)
            ->findOrFail($sampleId);

        $sample->update(['status' => 'ready']);

        return response()->json([
            'message' => 'Sample marked as ready',
            'data' => $sample,
        ]);
    }

    public function storePengujian(Request $request, $id)
    {
        $beritaAcara = BeritaAcara::whereHas('analysts', function ($q) {
                $q->where('users.id', auth()->id());
            })->findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'agenda' => 'nullable|string|max:255',
            'jumlah_pengujian' => 'nullable|string|max:255',
            'nama_pengujian' => 'nullable|string|max:255',
            'tanggal_permohonan' => 'nullable|date',
            'tanggal_pengujian' => 'nullable|date',
            'analis' => 'nullable|string|max:255',
            'pengambilan_hasil' => 'nullable|string',
            'nama_file_pengujian' => 'nullable|string|max:255',
            'keterangan_pengujian' => 'nullable|string',
            'verifikator' => 'nullable|string|max:255',
        ]);

        $validated['berita_acara_id'] = $beritaAcara->id;

        $pengujian = Pengujian::updateOrCreate(
            ['berita_acara_id' => $beritaAcara->id],
            $validated
        );

        return response()->json([
            'message' => 'Pengujian saved successfully',
            'data' => $pengujian,
        ]);
    }

    public function storeRekapAnalis(Request $request, $id)
    {
        $beritaAcara = BeritaAcara::whereHas('analysts', function ($q) {
                $q->where('users.id', auth()->id());
            })->findOrFail($id);

        $validated = $request->validate([
            'rekap_analis_data' => 'required|string',
            'rekap_analis_template_id' => 'nullable|integer|exists:form_templates,id',
            'biaya' => 'nullable|numeric|min:0',
        ]);

        $data = json_decode($validated['rekap_analis_data'], true);
        if (!is_array($data) || empty($data)) {
            $data = (object) [];
        }

        $updateData = [
            'rekap_analis_data' => $data,
            'rekap_analis_template_id' => $validated['rekap_analis_template_id'] ?? null,
        ];

        if (isset($validated['biaya'])) {
            $updateData['biaya'] = $validated['biaya'];
        }

        $beritaAcara->update($updateData);

        // Update all related samples status to 'taken'
        Sample::where('berita_acara_id', $beritaAcara->id)
            ->whereIn('status', ['processing', 'ready'])
            ->update(['status' => 'taken']);

        return response()->json([
            'message' => 'Rekap analis saved successfully',
            'data' => $beritaAcara->fresh(),
        ]);
    }

    public function requestDiscount(Request $request, $id)
    {
        $beritaAcara = BeritaAcara::whereHas('analysts', function ($q) {
                $q->where('users.id', auth()->id());
            })->findOrFail($id);

        $validated = $request->validate([
            'discount_request' => 'required|numeric|min:0',
            'discount_reason' => 'nullable|string|max:1000',
        ]);

        $payment = $beritaAcara->payment()->updateOrCreate(
            ['berita_acara_id' => $beritaAcara->id],
            [
                'discount_request' => $validated['discount_request'],
                'discount_reason' => $validated['discount_reason'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Discount request submitted',
            'data' => $payment,
        ]);
    }
}
