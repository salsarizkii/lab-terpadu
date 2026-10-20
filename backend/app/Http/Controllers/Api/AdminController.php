<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\BeritaAcaraRevision;
use App\Models\Payment;
use App\Models\Sample;
use App\Models\SystemActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\SystemActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function beritaAcaraIndex()
    {
        $query = BeritaAcara::with(['user', 'analyst', 'analysts', 'penyelias', 'managerTeknis', 'revisions', 'payment', 'samples', 'certificate', 'pengujians']);

        // Jika hanya punya permission riwayat personal, filter milik sendiri
        if (!auth()->user()->hasPermission('berita_acara_riwayat_semua')) {
            $query->where('user_id', auth()->id());
        }

        $beritaAcaras = $query->orderBy('created_at', 'desc')->get();

        return response()->json($beritaAcaras);
    }

    public function beritaAcaraShow($id)
    {
        $query = BeritaAcara::with(['user', 'analyst', 'analysts', 'penyelias', 'managerTeknis', 'revisions', 'payment', 'samples', 'certificate', 'activityLogs.user']);

        // RBAC: tanpa permission riwayat semua, hanya boleh lihat BA milik sendiri
        if (!auth()->user()->hasPermission('berita_acara_riwayat_semua')) {
            $query->where('user_id', auth()->id());
        }

        return response()->json($query->findOrFail($id));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:admin_verified,revision,rejected',
            'note' => 'required_if:status,revision|string',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:1000',
            'nomor_agenda' => [
                'required_if:status,admin_verified',
                'nullable',
                'string',
                'max:100',
                Rule::unique('berita_acaras', 'nomor_agenda')->ignore($id),
            ],
        ]);

        $beritaAcara = BeritaAcara::findOrFail($id);

        if ($beritaAcara->status !== 'pending') {
            return response()->json([
                'message' => 'Berita acara sedang tidak menunggu verifikasi admin (status: ' . $beritaAcara->status . ')',
            ], 422);
        }

        $data = ['status' => $request->status];

        if ($request->filled('nomor_agenda')) {
            $data['nomor_agenda'] = $request->nomor_agenda;
        }

        if ($request->status === 'admin_verified') {
            $data['rejection_reason'] = null;
        }

        if ($request->status === 'rejected') {
            $data['rejection_reason'] = $request->input('rejection_reason');
        }

        $beritaAcara->update($data);

        if ($request->status === 'revision') {
            $revision = BeritaAcaraRevision::create([
                'berita_acara_id' => $beritaAcara->id,
                'note' => $request->note,
                'previous_form_data' => $beritaAcara->form_data,
                'created_by' => auth()->id(),
            ]);
            ActivityLogService::log($beritaAcara->id, 'revision_requested', $request->note, null, $revision->id);
        } elseif ($request->status === 'admin_verified') {
            ActivityLogService::log($beritaAcara->id, 'admin_verified', 'Menyetujui berita acara');
        } elseif ($request->status === 'rejected') {
            ActivityLogService::log($beritaAcara->id, 'rejected', 'Menolak berita acara: ' . ($request->input('rejection_reason') ?? ''));
        }

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $beritaAcara->load(['user', 'analysts', 'penyelias', 'managerTeknis', 'revisions', 'payment', 'samples', 'certificate', 'activityLogs.user']),
        ]);
    }

    /**
     * Cek apakah nomor agenda sudah dipakai oleh berita acara lain.
     */
    public function checkNomorAgenda(Request $request)
    {
        $request->validate([
            'nomor' => 'required|string|max:100',
            'ignore_id' => 'nullable|integer',
        ]);

        $exists = BeritaAcara::where('nomor_agenda', $request->nomor)
            ->when($request->filled('ignore_id'), fn ($q) => $q->where('id', '!=', $request->ignore_id))
            ->exists();

        return response()->json([
            'available' => !$exists,
        ]);
    }

    /**
     * Simpan nomor agenda tanpa mengubah status (dipanggil saat admin mengetik).
     */
    public function updateNomorAgenda(Request $request, $id)
    {
        $request->validate([
            'nomor_agenda' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('berita_acaras', 'nomor_agenda')->ignore($id),
            ],
        ]);

        $beritaAcara = BeritaAcara::findOrFail($id);

        if (!in_array($beritaAcara->status, ['draft', 'pending', 'revision'], true)) {
            return response()->json(['message' => 'Nomor agenda tidak dapat diubah setelah diverifikasi'], 422);
        }

        $beritaAcara->nomor_agenda = $request->nomor_agenda ?: null;
        $beritaAcara->save();

        ActivityLogService::log($beritaAcara->id, 'nomor_agenda', 'Menetapkan nomor agenda: ' . ($beritaAcara->nomor_agenda ?: '-'));

        return response()->json([
            'message' => 'Nomor agenda diperbarui',
            'data' => $beritaAcara->load('activityLogs.user'),
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

        $newIds = $validAnalysts->sort()->values();
        $currentIds = $beritaAcara->analysts->pluck('id')->sort()->values();
        $changed = $newIds->diff($currentIds)->isNotEmpty() || $currentIds->diff($newIds)->isNotEmpty();

        $beritaAcara->analysts()->sync($validAnalysts);

        if ($changed) {
            $names = User::whereIn('id', $validAnalysts)->pluck('name')->implode(', ');
            ActivityLogService::log($beritaAcara->id, 'analyst_assigned', 'Menugaskan analis: ' . $names);
        }

        return response()->json([
            'message' => $changed ? 'Analis berhasil di-assign' : 'Tidak ada perubahan penugasan analis',
            'data' => $beritaAcara->load('analysts'),
        ]);
    }

    public function paymentIndex()
    {
        $payments = Payment::with('beritaAcara.user', 'beritaAcara.activityLogs.user')
            ->orderByDesc('created_at')
            ->get();

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

        ActivityLogService::log(
            $payment->berita_acara_id,
            $request->status === 'approved' ? 'payment_confirmed' : 'payment_rejected',
            $request->status === 'approved' ? 'Menyetujui pembayaran' : 'Menolak pembayaran: ' . ($request->input('rejection_reason') ?? '')
        );

        return response()->json([
            'message' => 'Payment confirmed successfully',
            'data' => $payment->load('beritaAcara.user', 'beritaAcara.activityLogs.user'),
        ]);
    }

    public function sampleIndex()
    {
        $query = Sample::with('beritaAcara.user', 'beritaAcara.analyst', 'beritaAcara.analysts', 'beritaAcara.payment', 'beritaAcara.formula')
            ->whereHas('beritaAcara', function ($q) {
                $q->whereIn('status', ['penyelia_verified', 'rekap_ready', 'completed']);
            });

        if (!auth()->user()->hasPermission('hasil_pengujian_riwayat_semua')) {
            $query->whereHas('beritaAcara', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        $samples = $query->orderByDesc('created_at')->get();

        return response()->json($samples);
    }

    public function markSampleTaken($id)
    {
        $sample = Sample::findOrFail($id);
        $sample->update(['status' => 'taken']);

        ActivityLogService::log($sample->berita_acara_id, 'sample_taken', 'Menandai sampel diambil');

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
     * Daftar user dengan role penyelia.
     */
    public function getPenyelias()
    {
        $penyelias = User::where('role', 'penyelia')->get();

        return response()->json($penyelias);
    }

    public function assignPenyelia(Request $request, $id)
    {
        $request->validate([
            'penyelia_ids' => 'required|array|min:1',
            'penyelia_ids.*' => 'exists:users,id',
        ]);

        $beritaAcara = BeritaAcara::findOrFail($id);

        $validPenyelias = User::whereIn('id', $request->penyelia_ids)
            ->where('role', 'penyelia')
            ->pluck('id');

        if ($validPenyelias->isEmpty()) {
            return response()->json(['message' => 'Tidak ada penyelia valid yang dipilih'], 422);
        }

        $newIds = $validPenyelias->sort()->values();
        $currentIds = $beritaAcara->penyelias->pluck('id')->sort()->values();
        $changed = $newIds->diff($currentIds)->isNotEmpty() || $currentIds->diff($newIds)->isNotEmpty();

        $beritaAcara->penyelias()->sync($validPenyelias);

        if ($changed) {
            $names = User::whereIn('id', $validPenyelias)->pluck('name')->implode(', ');
            ActivityLogService::log($beritaAcara->id, 'penyelia_assigned', 'Menugaskan penyelia: ' . $names);
        }

        return response()->json([
            'message' => $changed ? 'Penyelia berhasil di-assign' : 'Tidak ada perubahan penugasan penyelia',
            'data' => $beritaAcara->load('penyelias'),
        ]);
    }

    /**
     * Daftar user dengan role manager teknis.
     */
    public function getManagerTeknis()
    {
        $managerTeknis = User::where('role', 'manager_teknis')->get();

        return response()->json($managerTeknis);
    }

    public function assignManagerTeknis(Request $request, $id)
    {
        $request->validate([
            'manager_teknis_ids' => 'required|array|min:1',
            'manager_teknis_ids.*' => 'exists:users,id',
        ]);

        $beritaAcara = BeritaAcara::findOrFail($id);

        $validManagers = User::whereIn('id', $request->manager_teknis_ids)
            ->where('role', 'manager_teknis')
            ->pluck('id');

        if ($validManagers->isEmpty()) {
            return response()->json(['message' => 'Tidak ada manager teknis valid yang dipilih'], 422);
        }

        $newIds = $validManagers->sort()->values();
        $currentIds = $beritaAcara->managerTeknis->pluck('id')->sort()->values();
        $changed = $newIds->diff($currentIds)->isNotEmpty() || $currentIds->diff($newIds)->isNotEmpty();

        $beritaAcara->managerTeknis()->sync($validManagers);

        if ($changed) {
            $names = User::whereIn('id', $validManagers)->pluck('name')->implode(', ');
            ActivityLogService::log($beritaAcara->id, 'manager_teknis_assigned', 'Menugaskan manager teknis: ' . $names);
        }

        return response()->json([
            'message' => $changed ? 'Manager teknis berhasil di-assign' : 'Tidak ada perubahan penugasan manager teknis',
            'data' => $beritaAcara->load('managerTeknis'),
        ]);
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

        $old = ['is_verified' => $user->is_verified];

        $user->update([
            'is_verified' => $request->is_verified,
        ]);

        $status = $request->is_verified ? 'diverifikasi' : 'ditolak';

        SystemActivityLogService::log(
            'user_verified',
            "Akun {$user->name} berhasil {$status}",
            User::class,
            $user->id,
            $old,
            ['is_verified' => $request->is_verified]
        );

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

        $old = ['is_verified' => $user->is_verified];

        $user->update(['is_verified' => false]);

        SystemActivityLogService::log(
            'user_deactivated',
            "Akun {$user->name} berhasil dinonaktifkan",
            User::class,
            $user->id,
            $old,
            ['is_verified' => false]
        );

        return response()->json([
            'message' => "Akun {$user->name} berhasil dinonaktifkan",
            'data' => $user,
        ]);
    }

    /**
     * Get system activity logs (audit trail).
     */
    public function systemActivityLogs(Request $request)
    {
        $query = SystemActivityLog::with('user:id,name,email,role');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 30));

        return response()->json($logs);
    }
}

