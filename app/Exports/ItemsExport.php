<?php

namespace App\Exports;

use App\Models\Item;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(protected ?string $term = null, protected array $filters = [])
    {
    }

    public function collection(): Collection
    {
        $query = Item::query();

        if (! auth()->user()?->canViewAllStoreStock()) {
            $query->where('store_id', auth()->user()?->store_id);
        } elseif (! empty($this->filters['store_id'])) {
            $query->where('store_id', $this->filters['store_id']);
        }

        if ($this->term) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->term}%")
                    ->orWhere('barcode', 'like', "%{$this->term}%");
            });
        }

        if (! empty($this->filters['category'])) {
            $query->where('category', $this->filters['category']);
        }

        if (! empty($this->filters['allocation'])) {
            $query->where('allocation', $this->filters['allocation']);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return ['Barcode', 'Nama Item', 'Allocation', 'Category', 'Sub Category', 'Price', 'Nilai Tiket', 'Stok', 'Status'];
    }

    public function map($item): array
    {
        return [
            $item->barcode,
            $item->name,
            $item->allocation ?? '-',
            $item->category ?? '-',
            $item->sub_category ?? '-',
            (float) $item->selling_price,
            $item->ticket_redeem_qty,
            $item->stock,
            $item->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
