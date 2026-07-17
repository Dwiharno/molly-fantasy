<?php

namespace App\Observers;

use App\Jobs\SyncToGoogleSheetsJob;
use App\Models\Item;
use App\Models\User;
use App\Notifications\StockMinimumNotification;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ItemObserver
{
    public function __construct(protected ActivityLogService $activityLog)
    {
    }

    public function created(Item $item): void
    {
        $this->activityLog->log(
            Auth::user(),
            'create',
            'master_item',
            "Menambahkan item baru: {$item->name} ({$item->barcode})",
            Item::class,
            $item->id,
            null,
            $item->toArray()
        );

        $this->syncToSheets($item);
    }

    public function updated(Item $item): void
    {
        $this->activityLog->log(
            Auth::user(),
            'update',
            'master_item',
            "Mengubah item: {$item->name} ({$item->barcode})",
            Item::class,
            $item->id,
            $item->getOriginal(),
            $item->getChanges()
        );

        if ($item->isDirty('stock') && $item->isLowStock()) {
            Log::channel('single')->warning("Stok minimum tercapai untuk item {$item->name} (sisa {$item->stock})");

            $admins = User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])->where('is_active', true)->get();
            Notification::send($admins, new StockMinimumNotification($item));
        }

        $this->syncToSheets($item);
    }

    public function deleted(Item $item): void
    {
        $this->activityLog->log(
            Auth::user(),
            'delete',
            'master_item',
            "Menghapus item: {$item->name} ({$item->barcode})",
            Item::class,
            $item->id,
            $item->toArray(),
            null
        );
    }

    protected function syncToSheets(Item $item): void
    {
        SyncToGoogleSheetsJob::dispatch('master_item', [
            $item->barcode,
            $item->name,
            $item->category ?? '-',
            (float) $item->selling_price,
            $item->stock,
            $item->is_active ? 'Aktif' : 'Nonaktif',
            now()->format('Y-m-d H:i:s'),
        ]);
    }
}
