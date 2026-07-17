<?php

namespace App\Repositories;

use App\Models\StockOpname;

class StockOpnameRepository extends BaseRepository
{
    public function __construct(StockOpname $model)
    {
        parent::__construct($model);
    }

    public function findActiveDraftForToday(int $userId): ?StockOpname
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereIn('status', ['draft', 'in_progress'])
            ->whereDate('opname_date', now()->toDateString())
            ->latest()
            ->first();
    }

    public function generateCode(): string
    {
        $prefix = 'SO-'.now()->format('Ymd').'-';
        $lastNumber = $this->model
            ->where('code', 'like', $prefix.'%')
            ->count();

        return $prefix.str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);
    }
}
