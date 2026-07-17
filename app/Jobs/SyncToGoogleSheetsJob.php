<?php

namespace App\Jobs;

use App\Models\GoogleSheetsSyncLog;
use App\Models\User;
use App\Notifications\GoogleSheetsOfflineNotification;
use App\Services\GoogleSheetsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SyncToGoogleSheetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        protected string $sheetKey,
        protected array $rowValues,
    ) {
    }

    public function handle(GoogleSheetsService $sheets): void
    {
        try {
            $success = $sheets->appendRow($this->sheetKey, $this->rowValues);

            GoogleSheetsSyncLog::create([
                'sheet_name' => $this->sheetKey,
                'status' => $success ? 'success' : 'skipped',
                'message' => $success ? 'Berhasil sinkronisasi.' : 'Google Sheets belum dikonfigurasi atau gagal.',
                'synced_at' => now(),
            ]);
        } catch (Throwable $e) {
            GoogleSheetsSyncLog::create([
                'sheet_name' => $this->sheetKey,
                'status' => 'failed',
                'message' => $e->getMessage(),
                'synced_at' => now(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        GoogleSheetsSyncLog::create([
            'sheet_name' => $this->sheetKey,
            'status' => 'failed',
            'message' => 'Gagal permanen setelah beberapa percobaan: '.$exception->getMessage(),
            'synced_at' => now(),
        ]);

        $admins = User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])->where('is_active', true)->get();
        Notification::send($admins, new GoogleSheetsOfflineNotification($this->sheetKey, $exception->getMessage()));
    }
}
