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
        $query = BeritaAcara::with(['user', 'analyst', 'analysts', 'revisions', 'payment', 'samples', 'certificate', 'pengujians']);

        // Jika hanya punya permission riwayat personal, filter milik sendiri
        if (!auth()->user()->hasPermission('berita_acara_riwayat_semua')) {
            $query->where('user_id', auth()->id());
        }

        $beritaAcaras = $query->orderBy('created_at', 'desc')->get();

        return response()->json($beritaAcaras);
    }

    public function beritaAcaraShow($id)
    {
        $beritaAcara = BeritaAcara::with(['user', 'analyst', 'analysts', 'revisions', 'payment', 'samples', 'certificate'])
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

        $data = ['status' => $request->status];

        $beritaAcara->update($data);

        if ($request->status === 'revision') {
            BeritaAcaraRevision::create([
                'berita_acara_id' => $beritaAcara->id,
                'note' => $request->note,
                'previous_form_data' => $beritaAcara->form_data,
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
            'analyst_ids' => 'required|array|min:1',
            'analyst_ids.*' => 'exists:users,id',
        ]);

        $beritaAcara = BeritaAcara::findOrFail($id);

        $validAnalysts = User::whereIn('id', $request->analyst_ids)
            ->whereHas('roleModel.permissions', function ($q) {
                $q->whereIn('name', ['hasil_pengujian_rekap']);
            })->pluck('id');

        if ($validAnalysts->isEmpty()) {
            return response()->json(['message' => 'Tidak ada analis valid yang dipilih'], 422);
        }

        $beritaAcara->analysts()->sync($validAnalysts);

        return response()->json([
            'message' => 'Analis berhasil di-assign',
            'data' => $beritaAcara->load('analysts'),
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
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:1000',
        ]);

        $payment = Payment::findOrFail($id);

        $data = ['status' => $request->status];
        if ($request->has('discount_approved')) {
            $data['discount_approved'] = $request->discount_approved;
            $data['discount_status'] = $request->status;
        }

        if ($request->status === 'rejected') {
            $data['rejection_reason'] = $request->input('rejection_reason');
        }

        $payment->update($data);

        return response()->json([
            'message' => 'Payment confirmed successfully',
            'data' => $payment->load('beritaAcara.user'),
        ]);
    }

    public function sampleIndex()
    {
        $query = Sample::with('beritaAcara.user', 'beritaAcara.analyst', 'beritaAcara.analysts', 'beritaAcara.payment', 'beritaAcara.formula')
            ->whereHas('beritaAcara.payment', function ($q) {
                $q->where('status', 'approved');
            });

        if (!auth()->user()->hasPermission('hasil_pengujian_riwayat_semua')) {
            $query->whereHas('beritaAcara', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        $samples = $query->get();

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

    /**
     * Get all registered users with search and filter support.
     */
    public function getUsers(Request $request)
    {
        $query = User::query();

        // Search by name, email, or phone
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        // Filter by verification status
        if ($request->has('is_verified')) {
            $isVerified = filter_var($request->query('is_verified'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isVerified !== null) {
                $query->where('is_verified', $isVerified);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json($users);
    }

    /**
     * Verify or reject a user account.
     */
    public function verifyUser(Request $request, $id)
    {
        $request->validate([
            'is_verified' => 'required|boolean',
        ]);

        $user = User::findOrFail($id);

        // Prevent modifying superadmin
        if ($user->role === 'superadmin') {
            return response()->json(['message' => 'Tidak dapat mengubah status superadmin'], 403);
        }

        $user->update([
            'is_verified' => $request->is_verified,
        ]);

        $status = $request->is_verified ? 'diverifikasi' : 'ditolak';

        return response()->json([
            'message' => "Akun {$user->name} berhasil {$status}",
            'data' => $user,
        ]);
    }

    public function deactivateUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'superadmin') {
            return response()->json(['message' => 'Tidak dapat menonaktifkan akun superadmin'], 403);
        }

        $user->update(['is_verified' => false]);

        return response()->json([
            'message' => "Akun {$user->name} berhasil dinonaktifkan",
            'data' => $user,
        ]);
    }
}

