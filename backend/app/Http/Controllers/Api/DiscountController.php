<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\Payment;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $beritaAcaras = BeritaAcara::with(['user', 'payment', 'samples'])
            ->where('status', 'approved')
            ->whereNotNull('biaya')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($ba) {
                return [
                    'id' => $ba->id,
                    'judul_penelitian' => $ba->judul_penelitian,
                    'pemohon' => $ba->user->name ?? '-',
                    'biaya' => $ba->biaya,
                    'diskon_diizinkan' => $ba->diskon_diizinkan,
                    'payment' => $ba->payment ? [
                        'id' => $ba->payment->id,
                        'amount' => $ba->payment->amount,
                        'discount_request' => $ba->payment->discount_request,
                        'discount_approved' => $ba->payment->discount_approved,
                        'discount_status' => $ba->payment->discount_status,
                        'discount_reason' => $ba->payment->discount_reason,
                        'discount_type' => $ba->payment->discount_type ?? null,
                        'discount_percent' => $ba->payment->discount_percent ?? null,
                        'discount_nominal' => $ba->payment->discount_nominal ?? null,
                        'status' => $ba->payment->status,
                    ] : null,
                    'has_discount' => $ba->payment && $ba->payment->discount_approved > 0,
                    'created_at' => $ba->created_at,
                ];
            });

        return response()->json($beritaAcaras);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'berita_acara_id' => 'required|exists:berita_acaras,id',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_nominal' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:1000',
        ]);

        $beritaAcara = BeritaAcara::findOrFail($validated['berita_acara_id']);
        $biaya = floatval($beritaAcara->biaya);

        $percentValue = floatval($validated['discount_percent'] ?? 0);
        $nominalValue = floatval($validated['discount_nominal'] ?? 0);

        if ($percentValue <= 0 && $nominalValue <= 0) {
            return response()->json(['message' => 'Isi minimal satu diskon (persen atau nominal)'], 422);
        }

        $percentAmount = round($biaya * $percentValue / 100);
        $totalDiscount = $percentAmount + $nominalValue;

        if ($totalDiscount > $biaya) {
            return response()->json(['message' => 'Total diskon tidak boleh melebihi biaya'], 422);
        }

        $payment = Payment::updateOrCreate(
            ['berita_acara_id' => $beritaAcara->id],
            [
                'amount' => $biaya,
                'discount_approved' => $totalDiscount,
                'discount_status' => 'approved',
                'discount_reason' => $validated['discount_reason'] ?? null,
                'discount_type' => 'combined',
                'discount_percent' => $percentValue > 0 ? $percentValue : null,
                'discount_nominal' => $nominalValue > 0 ? $nominalValue : null,
                'status' => 'approved',
            ]
        );

        return response()->json([
            'message' => 'Diskon berhasil diberikan',
            'data' => $payment,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_nominal' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:1000',
        ]);

        $payment = Payment::findOrFail($id);
        $biaya = floatval($payment->amount);

        $percentValue = floatval($validated['discount_percent'] ?? 0);
        $nominalValue = floatval($validated['discount_nominal'] ?? 0);

        if ($percentValue <= 0 && $nominalValue <= 0) {
            return response()->json(['message' => 'Isi minimal satu diskon (persen atau nominal)'], 422);
        }

        $percentAmount = round($biaya * $percentValue / 100);
        $totalDiscount = $percentAmount + $nominalValue;

        if ($totalDiscount > $biaya) {
            return response()->json(['message' => 'Total diskon tidak boleh melebihi biaya'], 422);
        }

        $payment->update([
            'discount_approved' => $totalDiscount,
            'discount_status' => 'approved',
            'discount_reason' => $validated['discount_reason'] ?? $payment->discount_reason,
            'discount_type' => 'combined',
            'discount_percent' => $percentValue > 0 ? $percentValue : null,
            'discount_nominal' => $nominalValue > 0 ? $nominalValue : null,
            'status' => 'approved',
        ]);

        return response()->json([
            'message' => 'Diskon berhasil diperbarui',
            'data' => $payment,
        ]);
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->update([
            'discount_approved' => null,
            'discount_status' => null,
            'discount_reason' => null,
            'discount_type' => null,
            'discount_percent' => null,
            'discount_nominal' => null,
        ]);

        return response()->json(['message' => 'Diskon berhasil dihapus']);
    }
}
