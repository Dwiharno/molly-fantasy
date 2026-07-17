<?php

namespace App\Repositories;

use App\Models\Item;
use App\Repositories\Contracts\ItemRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class ItemRepository extends BaseRepository implements ItemRepositoryInterface
{
    public function __construct(Item $model)
    {
        parent::__construct($model);
    }

    public function findByBarcode(string $barcode): ?Item
    {
        return $this->model->newQuery()->withTrashed()->where('barcode', $barcode)->first();
    }

    public function lowStock()
    {
        return $this->model->active()->lowStock()->get();
    }

    public function decrementStock(Item $item, int $qty, array $meta = []): Item
    {
        $before = $item->stock;
        $item->decrement('stock', $qty);
        $item->refresh();

        $item->stockMovements()->create([
            'type' => $meta['type'] ?? 'out',
            'quantity' => $qty,
            'stock_before' => $before,
            'stock_after' => $item->stock,
            'reference_type' => $meta['reference_type'] ?? null,
            'reference_id' => $meta['reference_id'] ?? null,
            'notes' => $meta['notes'] ?? null,
            'user_id' => Auth::id(),
        ]);

        return $item;
    }

    public function incrementStock(Item $item, int $qty, array $meta = []): Item
    {
        $before = $item->stock;
        $item->increment('stock', $qty);
        $item->refresh();

        $item->stockMovements()->create([
            'type' => $meta['type'] ?? 'in',
            'quantity' => $qty,
            'stock_before' => $before,
            'stock_after' => $item->stock,
            'reference_type' => $meta['reference_type'] ?? null,
            'reference_id' => $meta['reference_id'] ?? null,
            'notes' => $meta['notes'] ?? null,
            'user_id' => Auth::id(),
        ]);

        return $item;
    }

    public function search(?string $term, array $filters = [], int $perPage = 15)
    {
        $query = $this->model->newQuery();

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['allocation'])) {
            $query->where('allocation', $filters['allocation']);
        }

        if (! empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active'] === 'active');
        }

        if (! empty($filters['low_stock'])) {
            $query->lowStock();
        }

        return $query->latest()->paginate($perPage);
    }
}
