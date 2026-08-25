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
     */
    private function ensureRole(array $roles): void
    {
        if (!in_array(auth()->user()->role, $roles, true)) {
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
            ->where('status', 'admin_verified');

        if (!auth()->user()->hasPermission('berita_acara_riwayat_semua')) {
            $query->whereHas('penyelias', function ($q) {
                $q->where('users.id', auth()->id());
            });
        }

        return response()->json($query->orderByDesc('created_at')->get());
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
            'note' => 'required_if:status,revision|nullable|string',
        ]);

        return $this->applyVerification($beritaAcara, $validated['status'], $validated['note'] ?? null, 'penyelia_verified');
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
            'note' => 'required_if:status,revision|nullable|string',
        ]);

        return $this->applyVerification($beritaAcara, $validated['status'], $validated['note'] ?? null, 'rekap_verified');
    }

    /**
     * Terapkan status verifikasi + catat activity log & revision.
     */
    private function applyVerification(BeritaAcara $beritaAcara, string $newStatus, ?string $note, string $approveAction)
    {
        $oldStatus = $beritaAcara->status;
        $beritaAcara->update(['status' => $newStatus]);

        if ($newStatus === 'revision') {
            $revision = BeritaAcaraRevision::create([
                'berita_acara_id' => $beritaAcara->id,
                'note' => $note,
                'previous_form_data' => $beritaAcara->form_data,
                'created_by' => auth()->id(),
            ]);
            ActivityLogService::log($beritaAcara->id, 'revision_requested', $note, null, $revision->id);
        } elseif ($newStatus === 'rejected') {
            ActivityLogService::log($beritaAcara->id, 'rejected', 'Menolak berita acara');
        } else {
            ActivityLogService::log($beritaAcara->id, $approveAction, 'Menyetujui berita acara');
        }

        return response()->json([
            'message' => 'Status berita acara diperbarui',
            'data' => $beritaAcara->load(['user', 'analysts', 'penyelias', 'managerTeknis', 'revisions', 'payment', 'samples', 'certificate', 'activityLogs.user']),
        ]);
    }
}
