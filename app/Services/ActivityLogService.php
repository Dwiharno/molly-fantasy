<?php

namespace App\Services;

use App\Jobs\SyncToGoogleSheetsJob;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    public function log(
        ?User $user,
        string $action,
        string $module,
        string $description,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): ActivityLog {
        $log = ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
        ]);

        SyncToGoogleSheetsJob::dispatch('log', [
            now()->format('Y-m-d H:i:s'),
            $user?->name ?? 'System',
            $action,
            $module,
            $description,
        ]);

        return $log;
    }
}
