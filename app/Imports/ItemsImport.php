<?php

namespace App\Imports;

use App\Models\Item;
use App\Repositories\Contracts\ItemRepositoryInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Throwable;

class ItemsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    use Importable;

    public int $successCount = 0;

    public array $errors = [];

    public function __construct(protected ItemRepositoryInterface $itemRepository)
    {
    }

    /**
     * Format kolom Excel (heading row wajib ada, huruf kecil otomatis oleh WithHeadingRow):
     * barcode | nama | allocation | kategori | sub_kategori | harga | tiket | qty | status
     *
     * Allocation, kategori, dan sub_kategori HARUS persis sama dengan salah satu pilihan
     * tetap di sistem (lihat Item::ALLOCATIONS / CATEGORIES / SUB_CATEGORIES). Baris dengan
     * nilai yang tidak cocok akan tetap diimport tapi memakai pilihan pertama sebagai default,
     * dan dicatat di daftar peringatan supaya bisa dikoreksi manual.
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $barcode = trim((string) ($row['barcode'] ?? ''));
            $nama = trim((string) ($row['nama'] ?? ''));

            if ($barcode === '' || $nama === '') {
                $this->errors[] = 'Baris '.($index + 2).': barcode/nama kosong, dilewati.';

                continue;
            }

            if ($this->itemRepository->findByBarcode($barcode)) {
                $this->errors[] = "Baris ".($index + 2).": barcode {$barcode} sudah ada, dilewati.";

                continue;
            }

            $allocation = $this->matchFixedValue($row['allocation'] ?? null, Item::ALLOCATIONS, $index, 'Allocation');
            $category = $this->matchFixedValue($row['kategori'] ?? null, Item::CATEGORIES, $index, 'Category');
            $subCategory = $this->matchFixedValue($row['sub_kategori'] ?? null, Item::SUB_CATEGORIES, $index, 'Sub Category');

            $status = strtolower(trim((string) ($row['status'] ?? 'aktif')));
            $qty = (int) ($row['qty'] ?? 0);

            try {
                $item = new Item([
                    'barcode' => $barcode,
                    'name' => $nama,
                    'allocation' => $allocation,
                    'category' => $category,
                    'sub_category' => $subCategory,
                    'stock' => 0,
                    'selling_price' => (float) ($row['harga'] ?? 0),
                    'ticket_redeem_qty' => (int) ($row['tiket'] ?? 1),
                    'minimum_stock' => 5,
                    'is_active' => ! in_array($status, ['nonaktif', 'tidak aktif', 'inactive', '0'], true),
                ]);
                $item->save();

                if ($qty > 0) {
                    $this->itemRepository->incrementStock($item, $qty, [
                        'type' => 'in',
                        'notes' => 'Import Excel',
                    ]);
                }

                $this->successCount++;
            } catch (Throwable $e) {
                $this->errors[] = 'Baris '.($index + 2).': barcode '.$barcode.' sudah ada atau gagal disimpan, dilewati.';
            }
        }
    }

    protected function matchFixedValue(?string $value, array $allowed, int $index, string $fieldLabel): string
    {
        $value = trim((string) $value);

        foreach ($allowed as $option) {
            if (strcasecmp($option, $value) === 0) {
                return $option;
            }
        }

        $this->errors[] = "Baris ".($index + 2).": {$fieldLabel} \"{$value}\" tidak dikenali, dipakai default \"{$allowed[0]}\".";

        return $allowed[0];
    }

    public function rules(): array
    {
        return [
            '*.barcode' => ['required'],
            '*.nama' => ['required'],
        ];
    }
}
