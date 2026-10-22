<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\BeritaAcaraRevision;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * Validasi role yang boleh mengakses endpoint ini.
     * Superadmin selalu diizinkan (override penuh).
     */
    private function ensureRole(array $roles): void
    {
        $user = auth()->user();
        if ($user->role === 'superadmin') {
            return;
        }
        if (!in_array($user->role, $roles, true)) {
            abort(403, 'Forbidden');
        }
    }

    /**
     * Ambil berita acara yang sedang menunggu verifikasi penyelia,
     * dibatasi pada penugasan penyelia kecuali punya permission riwayat semua.
     */
    private function findForPenyelia($id, bool $withRelations = true)
    {
        $this->ensureRole(['penyelia']);

        $query = BeritaAcara::query();
        if ($withRelations) {
            $query->with(['user', 'analysts', 'penyelias', 'managerTeknis', 'revisions', 'payment', 'samples', 'certificate', 'pengujians', 'activityLogs.user']);
        }
        if (!auth()->user()->hasPermission('berita_acara_riwayat_semua')) {
            $query->whereHas('penyelias', function ($q) {
                $q->where('users.id', auth()->id());
            });
        }
        return $query->findOrFail($id);
    }

    /**
     * Ambil berita acara untuk verifikasi manager teknis,
     * dibatasi pada penugasan manager kecuali punya permission riwayat semua.
     */
    private function findForManager($id, bool $withRelations = true)
    {
        $this->ensureRole(['manager_teknis']);

        $query = BeritaAcara::query();
        if ($withRelations) {
            $query->with(['user', 'analysts', 'penyelias', 'managerTeknis', 'revisions', 'payment', 'samples', 'certificate', 'pengujians', 'activityLogs.user']);
        }
        if (!auth()->user()->hasPermission('berita_acara_riwayat_semua')) {
            $query->whereHas('managerTeknis', function ($q) {
                $q->where('users.id', auth()->id());
            });
        }
        return $query->findOrFail($id);
    }

    public function penyeliaIndex()
    {
        $this->ensureRole(['penyelia']);

        $query = BeritaAcara::with(['user', 'analysts', 'penyelias', 'managerTeknis', 'revisions', 'payment', 'samples', 'certificate', 'pengujians'])
            ->whereIn('status', ['admin_verified', 'admin_revisi', 'penyelia_verified']);

        if (!auth()->user()->hasPermission('berita_acara_riwayat_semua')) {
            $query->whereHas('penyelias', function ($q) {
                $q->where('users.id', auth()->id());
            });
        }

        return response()->json(
            $query
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function penyeliaShow($id)
    {
        return response()->json($this->findForPenyelia($id));
    }

    public function penyeliaVerify(Request $request, $id)
    {
        $beritaAcara = $this->findForPenyelia($id, false);

        if ($beritaAcara->status !== 'admin_verified') {
            return response()->json([
                'message' => 'Berita acara sedang tidak menunggu verifikasi penyelia (status: ' . $beritaAcara->status . ')',
            ], 422);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:penyelia_verified,revision,rejected',
            'note' => 'required_if:status,revision|required_if:status,rejected|nullable|string|max:1000',
            'sample_answer' => 'nullable|string|in:sudah,belum',
        ]);

        return $this->applyVerification($beritaAcara, $validated['status'], $validated['note'] ?? null, 'penyelia_verified', $validated['sample_answer'] ?? null);
    }

    public function managerIndex()
    {
        $this->ensureRole(['manager_teknis']);

        $query = BeritaAcara::with(['user', 'analysts', 'penyelias', 'managerTeknis', 'revisions', 'payment', 'samples', 'certificate', 'pengujians'])
            ->where('status', 'rekap_ready');

        if (!auth()->user()->hasPermission('berita_acara_riwayat_semua')) {
            $query->whereHas('managerTeknis', function ($q) {
                $q->where('users.id', auth()->id());
            });
        }

        return response()->json($query->orderByDesc('created_at')->get());
    }

    public function managerShow($id)
    {
        return response()->json($this->findForManager($id));
    }

    public function managerVerify(Request $request, $id)
    {
        $beritaAcara = $this->findForManager($id, false);

        if ($beritaAcara->status !== 'rekap_ready') {
            return response()->json([
                'message' => 'Berita acara sedang tidak menunggu verifikasi manager teknis (status: ' . $beritaAcara->status . ')',
            ], 422);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:completed,revision,rejected',
            'note' => 'required_if:status,revision|required_if:status,rejected|nullable|string|max:1000',
        ]);

        return $this->applyVerification($beritaAcara, $validated['status'], $validated['note'] ?? null, 'rekap_verified');
    }

    /**
     * Terapkan status verifikasi + catat activity log & revision.
     * Catatan: semua aksi non-approve dari penyelia (revision maupun rejected)
     * mengembalikan BA ke antrian admin (status `admin_revisi`) untuk diperbaiki
     * & diverifikasi ulang oleh admin. Revisi ke user hanya berasal dari admin/manager.
     * Aksi oleh superadmin ditandai "(override superadmin)" pada log.
     */
    private function applyVerification(BeritaAcara $beritaAcara, string $newStatus, ?string $note, string $approveAction, ?string $sampleAnswer = null)
    {
        $oldStatus = $beritaAcara->status;
        $overrideSuffix = auth()->user()->role === 'superadmin' ? ' (override superadmin)' : '';

        $data = ['status' => $newStatus];
        if ($newStatus === 'rejected' && $approveAction === 'penyelia_verified') {
            $data = ['status' => 'admin_revisi', 'rejection_reason' => $note];
        } elseif ($newStatus === 'revision' && $approveAction === 'penyelia_verified') {
            $data = ['status' => 'admin_revisi', 'rejection_reason' => $note];
        } elseif ($newStatus === 'rejected') {
            $data['rejection_reason'] = $note;
        }
        $beritaAcara->update($data);

        if ($newStatus === 'revision') {
            if ($approveAction === 'penyelia_verified') {
                ActivityLogService::log($beritaAcara->id, 'admin_revision', 'Meminta perbaikan dari admin: ' . ($note ?? '') . $overrideSuffix);
            } else {
                $revision = BeritaAcaraRevision::create([
                    'berita_acara_id' => $beritaAcara->id,
                    'note' => $note,
                    'previous_form_data' => $beritaAcara->form_data,
                    'created_by' => auth()->id(),
                ]);
                ActivityLogService::log($beritaAcara->id, 'revision_requested', ($note ?? '') . $overrideSuffix, null, $revision->id);
            }
        } elseif ($newStatus === 'rejected') {
            if ($approveAction === 'penyelia_verified') {
                ActivityLogService::log($beritaAcara->id, 'admin_revision', 'Mengembalikan berita acara ke admin untuk diperbaiki: ' . ($note ?? '') . $overrideSuffix);
            } else {
                ActivityLogService::log($beritaAcara->id, 'rejected', 'Menolak berita acara: ' . ($note ?? '') . $overrideSuffix);
            }
        } else {
            ActivityLogService::log($beritaAcara->id, $approveAction, 'Menyetujui berita acara' . $overrideSuffix);
            if ($approveAction === 'penyelia_verified' && !empty($note)) {
                ActivityLogService::log($beritaAcara->id, 'penyelia_note', 'Catatan penyelia untuk analis: ' . $note . $overrideSuffix);
            }
            if ($approveAction === 'penyelia_verified' && $sampleAnswer === 'sudah' && !$beritaAcara->sample_submitted) {
                $beritaAcara->update([
                    'sample_submitted' => true,
                    'sample_submitted_at' => now(),
                ]);
                ActivityLogService::log($beritaAcara->id, 'sample_submitted', 'Sampel dikonfirmasi sudah diterima lab oleh penyelia');
            }
        }

        return response()->json([
            'message' => 'Status berita acara diperbarui',
            'data' => $beritaAcara->load(['user', 'analysts', 'penyelias', 'managerTeknis', 'revisions', 'payment', 'samples', 'certificate', 'activityLogs.user']),
        ]);
    }
}
