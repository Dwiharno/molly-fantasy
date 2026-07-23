<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\RedeemTransactionDetail;
use App\Models\StockOpnameDetail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Yajra\DataTables\Facades\DataTables;

class LaporanController extends Controller
{
    public function index(): View
    {
        return view('laporan.index', [
            'categories' => Item::CATEGORIES,
            'cashiers' => User::orderBy('name')->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return match ($request->get('type')) {
            'redeem_pos' => $this->redeemData($request, 'pos'),
            'redeem_member' => $this->redeemData($request, 'member'),
            'stock' => $this->stockData($request),
            'barang_masuk' => $this->movementData($request, ['in']),
            'barang_keluar' => $this->movementData($request, ['out', 'redeem']),
            'user' => $this->userData($request),
            'selisih_stock' => $this->selisihStockData($request),
            default => response()->json(['error' => 'Jenis laporan tidak valid.'], 422),
        };
    }

    protected function redeemData(Request $request, string $redeemType): JsonResponse
    {
        $query = RedeemTransactionDetail::with(['redeemTransaction.user', 'item'])
            ->whereHas('redeemTransaction', fn ($q) => $q->where('redeem_type', $redeemType));

        if ($request->filled('date_from')) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->whereDate('redeemed_at', '>=', $request->date_from));
        }
        if ($request->filled('date_to')) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->whereDate('redeemed_at', '<=', $request->date_to));
        }
        if ($request->filled('user_id')) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->where('user_id', $request->user_id));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('tanggal', fn ($d) => $d->redeemTransaction->redeemed_at->format('d/m/Y H:i'))
            ->addColumn('kasir', fn ($d) => $d->redeemTransaction->user->name ?? '-')
            ->addColumn('point', fn ($d) => (int) $d->qty > 0 ? intdiv((int) $d->ticket_used, (int) $d->qty) : 0)
            ->addColumn('jumlah_tiket', fn ($d) => (int) $d->redeemTransaction->total_ticket_scanned)
            ->addColumn('sisa_tiket', fn ($d) => max(0, (int) $d->redeemTransaction->total_ticket_scanned - (int) $d->redeemTransaction->total_ticket_used))
            ->addColumn('total_redeem', fn ($d) => (int) $d->redeemTransaction->total_ticket_used)
            ->addColumn('pos_label', fn ($d) => $d->redeemTransaction->redeem_type === 'member'
                ? 'Member'
                : ($d->redeemTransaction->pos_number ? 'Pos '.$d->redeemTransaction->pos_number : 'POS (data lama/offline)'))
            ->addColumn('unit_price', fn ($d) => (float) ($d->item?->selling_price ?? 0))
            ->addColumn('item_value', fn ($d) => (float) ($d->item?->selling_price ?? 0) * (int) $d->qty)
            ->make(true);
    }

    protected function stockData(Request $request): JsonResponse
    {
        $query = Item::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('unit_price', fn (Item $i) => (float) $i->selling_price)
            ->addColumn('item_value', fn (Item $i) => (float) $i->selling_price * (int) $i->stock)
            ->addColumn('status_label', fn (Item $i) => $i->is_active ? 'Aktif' : 'Nonaktif')
            ->make(true);
    }

    protected function movementData(Request $request, array $types): JsonResponse
    {
        $query = DB::table('item_stock_movements')
            ->join('items', 'items.id', '=', 'item_stock_movements.item_id')
            ->select('item_stock_movements.*', 'items.barcode as item_barcode', 'items.name as item_name', 'items.selling_price as unit_price')
            ->whereIn('item_stock_movements.type', $types);

        if ($request->filled('date_from')) {
            $query->whereDate('item_stock_movements.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('item_stock_movements.created_at', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('item_value', fn ($m) => (float) $m->unit_price * abs((int) $m->quantity))
            ->editColumn('created_at', fn ($m) => \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i'))
            ->make(true);
    }

    protected function userData(Request $request): JsonResponse
    {
        $query = User::query();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('role_label', fn (User $u) => User::ROLES[$u->role] ?? $u->role)
            ->addColumn('status_label', fn (User $u) => $u->is_active ? 'Aktif' : 'Nonaktif')
            ->addColumn('last_login', fn (User $u) => $u->last_login_at?->format('d/m/Y H:i') ?? '-')
            ->make(true);
    }

    protected function selisihStockData(Request $request): JsonResponse
    {
        $query = StockOpnameDetail::with(['item', 'stockOpname'])
            ->where('difference', '!=', 0)
            ->whereHas('stockOpname', fn ($q) => $q->where('status', 'completed'));

        if ($request->filled('date_from')) {
            $query->whereHas('stockOpname', fn ($q) => $q->whereDate('opname_date', '>=', $request->date_from));
        }
        if ($request->filled('date_to')) {
            $query->whereHas('stockOpname', fn ($q) => $q->whereDate('opname_date', '<=', $request->date_to));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('opname_code', fn ($d) => $d->stockOpname->code ?? '-')
            ->addColumn('opname_date', fn ($d) => $d->stockOpname->opname_date?->format('d/m/Y') ?? '-')
            ->addColumn('item_name', fn ($d) => $d->item->name ?? '(item dihapus)')
            ->addColumn('item_barcode', fn ($d) => $d->item->barcode ?? '-')
            ->addColumn('unit_price', fn ($d) => (float) ($d->item?->selling_price ?? 0))
            ->addColumn('item_value', fn ($d) => (float) ($d->item?->selling_price ?? 0) * (int) $d->difference)
            ->make(true);
    }

    public function exportExcel(Request $request)
    {
        $type = $request->get('type');
        $filename = 'laporan-'.$type.'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new \App\Exports\LaporanExport($type, $request->all()), $filename);
    }

    public function exportPdf(Request $request)
    {
        $type = $request->get('type');
        $data = $this->getReportCollectionForExport($type, $request);

        $pdf = Pdf::loadView('exports.laporan-pdf', [
            'type' => $type,
            'title' => $this->reportTitle($type),
            'rows' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-'.$type.'-'.now()->format('Ymd-His').'.pdf');
    }

    public function getReportCollectionForExport(string $type, Request $request)
    {
        return match ($type) {
            'redeem_pos' => RedeemTransactionDetail::with(['redeemTransaction.user', 'item'])
                ->whereHas('redeemTransaction', fn ($q) => $q->where('redeem_type', 'pos'))->latest()->get(),
            'redeem_member' => RedeemTransactionDetail::with(['redeemTransaction.user', 'item'])
                ->whereHas('redeemTransaction', fn ($q) => $q->where('redeem_type', 'member'))->latest()->get(),
            'stock' => Item::all(),
            'barang_masuk' => DB::table('item_stock_movements')->join('items', 'items.id', '=', 'item_stock_movements.item_id')
                ->select('item_stock_movements.*', 'items.barcode as item_barcode', 'items.name as item_name', 'items.selling_price as unit_price')
                ->where('type', 'in')->get(),
            'barang_keluar' => DB::table('item_stock_movements')->join('items', 'items.id', '=', 'item_stock_movements.item_id')
                ->select('item_stock_movements.*', 'items.barcode as item_barcode', 'items.name as item_name', 'items.selling_price as unit_price')
                ->whereIn('type', ['out', 'redeem'])->get(),
            'user' => User::all(),
            'selisih_stock' => StockOpnameDetail::with(['item', 'stockOpname'])->where('difference', '!=', 0)->get(),
            default => collect(),
        };
    }

    protected function reportTitle(string $type): string
    {
        return match ($type) {
            'redeem_pos' => 'Laporan Redeem POS',
            'redeem_member' => 'Laporan Redeem Member',
            'stock' => 'Laporan Stock',
            'barang_masuk' => 'Laporan Barang Masuk',
            'barang_keluar' => 'Laporan Barang Keluar',
            'user' => 'Laporan User',
            'selisih_stock' => 'Laporan Selisih Stock',
            default => 'Laporan',
        };
    }
}
