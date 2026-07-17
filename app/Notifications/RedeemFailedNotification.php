<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RedeemFailedNotification extends Notification
{
    use Queueable;

    public function __construct(protected string $reason, protected string $barcode)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'category' => 'redeem_failed',
            'title' => 'Redeem Gagal',
            'message' => "Redeem gagal untuk barcode {$this->barcode}: {$this->reason}",
            'icon' => 'fa-circle-xmark',
            'color' => 'danger',
        ];
    }
}
