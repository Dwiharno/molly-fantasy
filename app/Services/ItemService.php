<?php

namespace App\Services;

use App\Models\Item;
use App\Repositories\Contracts\ItemRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ItemService
{
    public function __construct(protected ItemRepositoryInterface $itemRepository)
    {
    }

    public function paginate(?string $term, array $filters, int $perPage = 15)
    {
        return $this->itemRepository->search($term, $filters, $perPage);
    }

    public function create(array $data, ?UploadedFile $image = null): Item
    {
        // Cegah duplicate barcode (validasi tambahan di level Service selain Form Request)
        $data['store_id'] ??= auth()->user()?->store_id ?? \App\Models\Store::where('code', 'S040')->value('id');
        if (Item::withTrashed()->where('barcode', $data['barcode'])->where('store_id', $data['store_id'])->exists()) {
            throw ValidationException::withMessages([
                'barcode' => 'Barcode sudah terdaftar untuk item lain.',
            ]);
        }

        return DB::transaction(function () use ($data, $image) {
            if ($image) {
                $data['image'] = $this->storeImage($image);
            }

            $data['is_active'] = $data['is_active'] ?? true;

            /** @var Item $item */
            $item = $this->itemRepository->create($data);

            if (($data['stock'] ?? 0) > 0) {
                $this->itemRepository->incrementStock($item, (int) $data['stock'], [
                    'type' => 'in',
                    'notes' => 'Stok awal saat pembuatan item',
                ]);
            }

            return $item->fresh();
        });
    }

    public function update(Item $item, array $data, ?UploadedFile $image = null): Item
    {
        return DB::transaction(function () use ($item, $data, $image) {
            if ($image) {
                $this->deleteImage($item->image);
                $data['image'] = $this->storeImage($image);
            }

            $item->update($data);

            return $item->fresh();
        });
    }

    public function delete(Item $item): bool
    {
        return DB::transaction(function () use ($item) {
            $this->deleteImage($item->image);

            return (bool) $item->delete();
        });
    }

    public function adjustStock(Item $item, int $newStock, string $notes = ''): Item
    {
        $diff = $newStock - $item->stock;

        if ($diff === 0) {
            return $item;
        }

        return $diff > 0
            ? $this->itemRepository->incrementStock($item, $diff, ['type' => 'adjustment', 'notes' => $notes])
            : $this->itemRepository->decrementStock($item, abs($diff), ['type' => 'adjustment', 'notes' => $notes]);
    }

    protected function storeImage(UploadedFile $image): string
    {
        return $image->store('items', 'public');
    }

    protected function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            try {
                Storage::disk('public')->delete($path);
            } catch (Throwable $e) {
                Log::warning("Gagal menghapus gambar item: {$e->getMessage()}");
            }
        }
    }
}
