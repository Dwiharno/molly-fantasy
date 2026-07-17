<?php

namespace App\Services;

use App\Jobs\SyncToGoogleSheetsJob;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Repositories\ItemRepository;
use App\Repositories\StockOpnameRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOpnameService
{
    public function __construct(
        protected StockOpnameRepository $stockOpnameRepository,
        protected ItemRepository $itemRepository,
        protected ActivityLogService $activityLog,
    ) {
    }

    public function startSession(?string $notes = null): StockOpname
    {
        $opname = $this->stockOpnameRepository->create([
            'code' => $this->stockOpnameRepository->generateCode(),
            'opname_date' => now()->toDateString(),
            'user_id' => Auth::id(),
            'status' => 'in_progress',
            'notes' => $notes,
        ]);

        $this->activityLog->log(Auth::user(), 'create', 'stock_opname', "Memulai sesi stock opname {$opname->code}");

        return $opname;
    }

    /**
     * Scan satu barcode. Mengembalikan array berisi status ('found'|'not_found')
     * dan detail hasil scan bila item ditemukan.
     */
    public function scan(StockOpname $opname, string $barcode): array
    {
        $this->ensureEditable($opname);

        $item = $this->itemRepository->findByBarcode($barcode);

        if (! $item) {
            return ['status' => 'not_found', 'barcode' => $barcode];
        }

        $detail = StockOpnameDetail::where('stock_opname_id', $opname->id)
            ->where('item_id', $item->id)
            ->first();

        if ($detail) {
            $detail->increment('actual_stock');
            $detail->update(['scanned_at' => now()]);
        } else {
            $detail = StockOpnameDetail::create([
                'stock_opname_id' => $opname->id,
                'item_id' => $item->id,
                'expected_stock' => $item->stock,
                'actual_stock' => 1,
                'difference' => 0,
                'scanned_at' => now(),
            ]);
        }

        $detail->refresh();

        return [
            'status' => 'found',
            'item' => $item,
            'detail' => $detail,
        ];
    }

    public function undoScan(StockOpname $opname, int $itemId): void
    {
        $this->ensureEditable($opname);

        $detail = StockOpnameDetail::where('stock_opname_id', $opname->id)
            ->where('item_id', $itemId)
            ->first();

        if (! $detail) {
            return;
        }

        if ($detail->actual_stock <= 1) {
            $detail->delete();
        } else {
            $detail->decrement('actual_stock');
        }
    }

    public function resetScan(StockOpname $opname): void
    {
        $this->ensureEditable($opname);

        StockOpnameDetail::where('stock_opname_id', $opname->id)->delete();

        $this->activityLog->log(Auth::user(), 'reset', 'stock_opname', "Reset seluruh scan pada sesi {$opname->code}");
    }

    /**
     * Simpan / finalisasi opname: hitung selisih, sesuaikan stok aktual di Master Item,
     * tandai sesi selesai.
     */
    public function complete(StockOpname $opname): StockOpname
    {
        $this->ensureEditable($opname);

        return DB::transaction(function () use ($opname) {
            $details = $opname->details()->with('item')->get();

            foreach ($details as $detail) {
                $difference = $detail->actual_stock - $detail->expected_stock;
                $detail->update(['difference' => $difference]);

                if ($difference !== 0 && $detail->item) {
                    $qty = abs($difference);

                    if ($difference > 0) {
                        $this->itemRepository->incrementStock($detail->item, $qty, [
                            'type' => 'opname',
                            'reference_type' => StockOpname::class,
                            'reference_id' => $opname->id,
                            'notes' => "Penyesuaian stock opname {$opname->code}",
                        ]);
                    } else {
                        $this->itemRepository->decrementStock($detail->item, $qty, [
                            'type' => 'opname',
                            'reference_type' => StockOpname::class,
                            'reference_id' => $opname->id,
                            'notes' => "Penyesuaian stock opname {$opname->code}",
                        ]);
                    }
                }

                SyncToGoogleSheetsJob::dispatch('stock_opname', [
                    $opname->code,
                    now()->format('Y-m-d H:i:s'),
                    $detail->item->barcode ?? '-',
                    $detail->item->name ?? '(item dihapus)',
                    $detail->expected_stock,
                    $detail->actual_stock,
                    $difference,
                ]);
            }

            $opname->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->activityLog->log(
                Auth::user(), 'complete', 'stock_opname',
                "Menyelesaikan stock opname {$opname->code} ({$details->count()} item dihitung)"
            );

            return $opname->fresh();
        });
    }

    protected function ensureEditable(StockOpname $opname): void
    {
        if ($opname->status === 'completed') {
            throw ValidationException::withMessages([
                'stock_opname' => 'Sesi stock opname ini sudah selesai dan tidak dapat diubah.',
            ]);
        }
    }
}
