<?php

namespace App\Repositories\Contracts;

use App\Models\Item;

interface ItemRepositoryInterface extends RepositoryInterface
{
    public function findByBarcode(string $barcode): ?Item;

    public function lowStock();

    public function decrementStock(Item $item, int $qty, array $meta = []): Item;

    public function incrementStock(Item $item, int $qty, array $meta = []): Item;

    public function search(?string $term, array $filters = [], int $perPage = 15);
}
