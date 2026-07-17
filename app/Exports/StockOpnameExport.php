<?php

namespace App\Exports;

use App\Models\StockOpname;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockOpnameExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(protected StockOpname $opname)
    {
    }

    public function collection(): Collection
    {
        return $this->opname->details()->with('item')->get();
    }

    public function headings(): array
    {
        return ['Barcode', 'Nama Item', 'Expected Stock', 'Actual Stock', 'Selisih'];
    }

    public function map($detail): array
    {
        return [
            $detail->item->barcode ?? '-',
            $detail->item->name ?? '(item dihapus)',
            $detail->expected_stock,
            $detail->actual_stock,
            $detail->difference,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
