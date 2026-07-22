<?php

namespace App\Exports;

use App\Models\RedeemTransactionDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RedeemHistoryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(protected array $filters = [])
    {
    }

    public function collection(): Collection
    {
        $query = RedeemTransactionDetail::with(['redeemTransaction.user']);

        if (! auth()->user()?->isSuperAdmin()) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->where('store_id', auth()->user()?->store_id));
        } elseif (! empty($this->filters['store_id'])) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->where('store_id', $this->filters['store_id']));
        }

        if (! empty($this->filters['date_from'])) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->whereDate('redeemed_at', '>=', $this->filters['date_from']));
        }
        if (! empty($this->filters['date_to'])) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->whereDate('redeemed_at', '<=', $this->filters['date_to']));
        }
        if (! empty($this->filters['user_id'])) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->where('user_id', $this->filters['user_id']));
        }
        if (! empty($this->filters['name'])) {
            $query->where('item_name', 'like', '%'.$this->filters['name'].'%');
        }
        if (! empty($this->filters['barcode'])) {
            $query->where('item_barcode', 'like', '%'.$this->filters['barcode'].'%');
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return ['Tanggal', 'No. Transaksi', 'Kasir', 'Barcode', 'Nama Barang', 'Qty', 'Tiket Terpakai', 'Tiket Discan', 'Sisa Tiket'];
    }

    public function map($detail): array
    {
        return [
            $detail->redeemTransaction->redeemed_at->format('d/m/Y H:i'),
            $detail->redeemTransaction->transaction_code,
            $detail->redeemTransaction->user->name ?? '-',
            $detail->item_barcode,
            $detail->item_name,
            $detail->qty,
            $detail->ticket_used,
            (int) $detail->redeemTransaction->total_ticket_scanned,
            max(0, (int) $detail->redeemTransaction->total_ticket_scanned - (int) $detail->redeemTransaction->total_ticket_used),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
