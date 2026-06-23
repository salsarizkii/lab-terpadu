<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\Sample;
use Illuminate\Http\Request;

class AnalystController extends Controller
{
    public function beritaAcaraIndex()
    {
        $beritaAcaras = BeritaAcara::where('analyst_id', auth()->id())
            ->with(['user', 'revisions', 'payment', 'samples', 'certificate'])
            ->get();

        return response()->json($beritaAcaras);
    }

    public function beritaAcaraShow($id)
    {
        $beritaAcara = BeritaAcara::where('analyst_id', auth()->id())
            ->with(['user', 'revisions', 'payment', 'samples', 'certificate'])
            ->findOrFail($id);

        return response()->json($beritaAcara);
    }

    public function updateSample(Request $request, $id, $sampleId)
    {
        $beritaAcara = BeritaAcara::where('analyst_id', auth()->id())->findOrFail($id);

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
        $beritaAcara = BeritaAcara::where('analyst_id', auth()->id())->findOrFail($id);

        $sample = Sample::where('berita_acara_id', $beritaAcara->id)
            ->findOrFail($sampleId);

        $sample->update(['status' => 'ready']);

        return response()->json([
            'message' => 'Sample marked as ready',
            'data' => $sample,
        ]);
    }
}
