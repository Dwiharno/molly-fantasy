<?php

namespace App\Exports;

use App\Models\Item;
use App\Models\RedeemTransactionDetail;
use App\Models\StockOpnameDetail;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(protected string $type, protected array $filters = [])
    {
    }

    public function collection(): Collection
    {
        return match ($this->type) {
            'redeem_pos' => $this->applyRedeemFilters(RedeemTransactionDetail::with(['redeemTransaction.user', 'item'])
                ->whereHas('redeemTransaction', fn ($q) => $q->where('redeem_type', 'pos')))->latest()->get(),
            'redeem_member' => $this->applyRedeemFilters(RedeemTransactionDetail::with(['redeemTransaction.user', 'item'])
                ->whereHas('redeemTransaction', fn ($q) => $q->where('redeem_type', 'member')))->latest()->get(),
            'stock' => $this->applyStockFilters(Item::query())->get(),
            'barang_masuk' => $this->movementQuery(['in'])->get(),
            'barang_keluar' => $this->movementQuery(['out', 'redeem'])->get(),
            'user' => User::all(),
            'selisih_stock' => StockOpnameDetail::with(['item', 'stockOpname'])->where('difference', '!=', 0)->get(),
            default => collect(),
        };
    }

    protected function applyRedeemFilters($query)
    {
        if (! empty($this->filters['date_from'])) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->whereDate('redeemed_at', '>=', $this->filters['date_from']));
        }
        if (! empty($this->filters['date_to'])) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->whereDate('redeemed_at', '<=', $this->filters['date_to']));
        }
        if (! empty($this->filters['user_id'])) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->where('user_id', $this->filters['user_id']));
        }

        return $query;
    }

    protected function applyStockFilters($query)
    {
        if (! empty($this->filters['category'])) {
            $query->where('category', $this->filters['category']);
        }

        return $query;
    }

    protected function movementQuery(array $types)
    {
        $query = DB::table('item_stock_movements')
            ->join('items', 'items.id', '=', 'item_stock_movements.item_id')
            ->select('item_stock_movements.*', 'items.barcode as item_barcode', 'items.name as item_name', 'items.selling_price as unit_price')
            ->whereIn('item_stock_movements.type', $types);

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('item_stock_movements.created_at', '>=', $this->filters['date_from']);
        }
        if (! empty($this->filters['date_to'])) {
            $query->whereDate('item_stock_movements.created_at', '<=', $this->filters['date_to']);
        }

        return $query;
    }

    public function headings(): array
    {
        return match ($this->type) {
            'redeem_pos', 'redeem_member' => ['Tanggal', 'No. Transaksi', 'Kasir', 'Barcode', 'Nama Barang', 'Qty', 'Harga Satuan', 'Total Value', 'Tiket'],
            'stock' => ['Barcode', 'Nama Item', 'Kategori', 'Stok', 'Harga Satuan', 'Total Value', 'Min. Stok', 'Status'],
            'barang_masuk', 'barang_keluar' => ['Tanggal', 'Barcode', 'Nama Item', 'Qty', 'Harga Satuan', 'Total Value', 'Catatan'],
            'user' => ['Nama', 'Email', 'Role', 'Status', 'Login Terakhir'],
            'selisih_stock' => ['Kode Opname', 'Tanggal', 'Barcode', 'Nama Item', 'Expected', 'Actual', 'Selisih', 'Harga Satuan', 'Total Value Selisih'],
            default => [],
        };
    }

    public function map($row): array
    {
        return match ($this->type) {
            'redeem_pos', 'redeem_member' => [
                $row->redeemTransaction->redeemed_at->format('d/m/Y H:i'),
                $row->redeemTransaction->transaction_code,
                $row->redeemTransaction->user->name ?? '-',
                $row->item_barcode,
                $row->item_name,
                $row->qty,
                (float) ($row->item?->selling_price ?? 0),
                (float) ($row->item?->selling_price ?? 0) * (int) $row->qty,
                $row->ticket_used,
            ],
            'stock' => [
                $row->barcode, $row->name, $row->category ?? '-',
                $row->stock, (float) $row->selling_price, (float) $row->selling_price * (int) $row->stock,
                $row->minimum_stock, $row->is_active ? 'Aktif' : 'Nonaktif',
            ],
            'barang_masuk', 'barang_keluar' => [
                \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i'),
                $row->item_barcode, $row->item_name, $row->quantity, (float) $row->unit_price,
                (float) $row->unit_price * abs((int) $row->quantity), $row->notes ?? '-',
            ],
            'user' => [
                $row->name, $row->email, User::ROLES[$row->role] ?? $row->role,
                $row->is_active ? 'Aktif' : 'Nonaktif', $row->last_login_at?->format('d/m/Y H:i') ?? '-',
            ],
            'selisih_stock' => [
                $row->stockOpname->code ?? '-',
                $row->stockOpname->opname_date?->format('d/m/Y') ?? '-',
                $row->item->barcode ?? '-',
                $row->item->name ?? '(item dihapus)',
                $row->expected_stock, $row->actual_stock, $row->difference,
                (float) ($row->item?->selling_price ?? 0),
                (float) ($row->item?->selling_price ?? 0) * (int) $row->difference,
            ],
            default => [],
        };
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
