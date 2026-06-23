<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\BeritaAcaraRevision;
use App\Models\Payment;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function beritaAcaraIndex()
    {
        $beritaAcaras = BeritaAcara::with(['user', 'analyst', 'revisions', 'payment', 'samples', 'certificate'])
            ->get();

        return response()->json($beritaAcaras);
    }

    public function beritaAcaraShow($id)
    {
        $beritaAcara = BeritaAcara::with(['user', 'analyst', 'revisions', 'payment', 'samples', 'certificate'])
            ->findOrFail($id);

        return response()->json($beritaAcara);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:approved,revision,rejected',
            'note' => 'required_if:status,revision|string',
        ]);

        $beritaAcara = BeritaAcara::findOrFail($id);

        $beritaAcara->update(['status' => $request->status]);

        if ($request->status === 'revision') {
            BeritaAcaraRevision::create([
                'berita_acara_id' => $beritaAcara->id,
                'note' => $request->note,
                'created_by' => auth()->id(),
            ]);
        }

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $beritaAcara->load(['revisions', 'payment', 'samples', 'certificate']),
        ]);
    }

    public function assignAnalyst(Request $request, $id)
    {
        $request->validate([
            'analyst_id' => 'required|exists:users,id',
        ]);

        $analyst = User::where('id', $request->analyst_id)->where('role', 'analyst')->first();

        if (!$analyst) {
            return response()->json(['message' => 'Invalid analyst'], 422);
        }

        $beritaAcara = BeritaAcara::findOrFail($id);
        $beritaAcara->update(['analyst_id' => $request->analyst_id]);

        return response()->json([
            'message' => 'Analyst assigned successfully',
            'data' => $beritaAcara->load('analyst'),
        ]);
    }

    public function paymentIndex()
    {
        $payments = Payment::with('beritaAcara.user')->get();

        return response()->json($payments);
    }

    public function confirmPayment(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:approved,rejected',
            'discount_approved' => 'nullable|numeric|min:0',
        ]);

        $payment = Payment::findOrFail($id);

        $data = ['status' => $request->status];
        if ($request->has('discount_approved')) {
            $data['discount_approved'] = $request->discount_approved;
        }

        $payment->update($data);

        return response()->json([
            'message' => 'Payment confirmed successfully',
            'data' => $payment,
        ]);
    }

    public function sampleIndex()
    {
        $samples = Sample::with('beritaAcara.user')->get();

        return response()->json($samples);
    }

    public function markSampleTaken($id)
    {
        $sample = Sample::findOrFail($id);
        $sample->update(['status' => 'taken']);

        return response()->json([
            'message' => 'Sample marked as taken',
            'data' => $sample,
        ]);
    }

    public function getAnalysts()
    {
        $analysts = User::where('role', 'analyst')->get();

        return response()->json($analysts);
    }
}
