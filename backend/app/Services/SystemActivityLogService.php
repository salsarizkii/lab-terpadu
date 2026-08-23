<?php

namespace App\Services;

use App\Models\SystemActivityLog;

class SystemActivityLogService
{
    /**
     * Catat aktivitas sistem (di luar berita_acara).
     */
    public static function log(
        string $action,
        ?string $description = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): SystemActivityLog {
        return SystemActivityLog::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
