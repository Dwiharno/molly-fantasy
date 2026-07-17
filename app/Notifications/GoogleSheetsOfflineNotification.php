<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GoogleSheetsOfflineNotification extends Notification
{
    use Queueable;

    public function __construct(protected string $sheetKey, protected string $errorMessage)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'category' => 'google_sheets_offline',
            'title' => 'Google Sheets Offline',
            'message' => "Sinkronisasi ke Google Sheets [{$this->sheetKey}] gagal: {$this->errorMessage}",
            'icon' => 'fa-cloud-arrow-up',
            'color' => 'danger',
        ];
    }
}
