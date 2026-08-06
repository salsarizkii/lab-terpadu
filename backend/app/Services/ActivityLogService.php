<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogService
{
    /**
     * Catat aktivitas pada sebuah berita acara. Aktor default = user yang login.
     */
    public static function log(int $beritaAcaraId, string $action, ?string $description = null, ?int $userId = null, ?int $revisionId = null): ActivityLog
    {
        return ActivityLog::create([
            'berita_acara_id' => $beritaAcaraId,
            'revision_id' => $revisionId,
            'action' => $action,
            'description' => $description,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }
}
