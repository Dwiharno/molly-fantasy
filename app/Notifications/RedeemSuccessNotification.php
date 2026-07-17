<?php

namespace App\Notifications;

use App\Models\RedeemTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RedeemSuccessNotification extends Notification
{
    use Queueable;

    public function __construct(protected RedeemTransaction $transaction, protected string $itemName, protected int $ticketUsed)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'category' => 'redeem_success',
            'title' => 'Redeem Berhasil',
            'message' => "Berhasil redeem \"{$this->itemName}\" menggunakan {$this->ticketUsed} tiket (Transaksi {$this->transaction->transaction_code}).",
            'transaction_id' => $this->transaction->id,
            'icon' => 'fa-circle-check',
            'color' => 'success',
        ];
    }
}
