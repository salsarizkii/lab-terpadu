<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\DiscountHistory;
use App\Models\Payment;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = BeritaAcara::with(['user', 'payment', 'samples'])
            ->where('status', 'approved')
            ->whereNotNull('biaya');

        if (!$user->hasPermission('discount_riwayat_semua')) {
            $query->where('user_id', $user->id);
        }

        $beritaAcaras = $query->orderByDesc('created_at')
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

    public function requests()
    {
        $payments = Payment::with(['beritaAcara.user', 'beritaAcara.activityLogs.user'])
            ->whereHas('beritaAcara')
            ->whereIn('discount_status', ['pending', 'rejected', 'approved'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($payment) {
                $ba = $payment->beritaAcara;
                return [
                    'id' => $payment->id,
                    'berita_acara_id' => $ba->id,
                    'judul_penelitian' => $ba->judul_penelitian,
                    'pemohon' => $ba->user->name ?? '-',
                    'biaya' => $ba->biaya,
                    'amount' => $payment->amount,
                    'discount_request' => $payment->discount_request,
                    'discount_approved' => $payment->discount_approved,
                    'discount_reason' => $payment->discount_reason,
                    'discount_status' => $payment->discount_status,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at,
                    'activity_logs' => $ba->activityLogs,
                ];
            });

        return response()->json($payments);
    }

    public function approve(Request $request, $id)
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
        ]);

        DiscountHistory::create([
            'payment_id' => $payment->id,
            'discount_request' => $payment->discount_request,
            'discount_approved' => $totalDiscount,
            'discount_reason' => $validated['discount_reason'] ?? $payment->discount_reason,
            'status' => 'approved',
            'action' => 'approved',
            'changed_by' => auth()->id(),
        ]);

        ActivityLogService::log($payment->berita_acara_id, 'discount_approved', 'Menyetujui diskon sebesar Rp ' . number_format($totalDiscount, 0, ',', '.'));

        return response()->json([
            'message' => 'Ajuan diskon disetujui',
            'data' => $payment,
        ]);
    }

    public function reject($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->update([
            'discount_status' => 'rejected',
            'discount_approved' => null,
        ]);

        DiscountHistory::create([
            'payment_id' => $payment->id,
            'discount_request' => $payment->discount_request,
            'discount_reason' => $payment->discount_reason,
            'status' => 'rejected',
            'action' => 'rejected',
            'changed_by' => auth()->id(),
        ]);

        ActivityLogService::log($payment->berita_acara_id, 'discount_rejected', 'Menolak ajuan diskon');

        return response()->json(['message' => 'Ajuan diskon ditolak']);
    }

    public function history($id)
    {
        $histories = DiscountHistory::with('changedBy:id,name,email,role')
            ->where('payment_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($histories);
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
            ]
        );

        ActivityLogService::log($beritaAcara->id, 'discount_given', 'Memberikan diskon sebesar Rp ' . number_format($totalDiscount, 0, ',', '.'));

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
        ]);

        ActivityLogService::log($payment->berita_acara_id, 'discount_updated', 'Memperbarui diskon menjadi Rp ' . number_format($totalDiscount, 0, ',', '.'));

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

        ActivityLogService::log($payment->berita_acara_id, 'discount_removed', 'Menghapus diskon');

        return response()->json(['message' => 'Diskon berhasil dihapus']);
    }
}
