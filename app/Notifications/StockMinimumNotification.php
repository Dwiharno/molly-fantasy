<?php

namespace App\Notifications;

use App\Models\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockMinimumNotification extends Notification
{
    use Queueable;

    public function __construct(protected Item $item)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'category' => 'stock_minimum',
            'title' => 'Stok Minimum Tercapai',
            'message' => "Item \"{$this->item->name}\" ({$this->item->barcode}) tersisa {$this->item->stock}, di bawah/sama dengan minimum stok ({$this->item->minimum_stock}).",
            'item_id' => $this->item->id,
            'icon' => 'fa-triangle-exclamation',
            'color' => 'warning',
        ];
    }
}
