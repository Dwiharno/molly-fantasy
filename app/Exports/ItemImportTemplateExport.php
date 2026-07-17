<?php

namespace App\Exports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemImportTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                '1234567890123',
                'Boneka Beruang Kecil',
                Item::ALLOCATIONS[1],
                Item::CATEGORIES[1],
                Item::SUB_CATEGORIES[6],
                15000,
                5,
                10,
                'Aktif',
            ],
        ];
    }

    public function headings(): array
    {
        return ['barcode', 'nama', 'allocation', 'kategori', 'sub_kategori', 'harga', 'tiket', 'qty', 'status'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
