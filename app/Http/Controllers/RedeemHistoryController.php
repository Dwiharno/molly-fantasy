<?php

namespace App\Http\Controllers;

use App\Exports\RedeemHistoryExport;
use App\Models\RedeemTransactionDetail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Yajra\DataTables\Facades\DataTables;

class RedeemHistoryController extends Controller
{
    public function index(): View
    {
        return view('redeem.history', [
            'cashiers' => User::whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
                ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->where('store_id', auth()->user()->store_id))
                ->orderBy('name')->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = RedeemTransactionDetail::with(['redeemTransaction.user']);

        $this->applyFilters($query, $request);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('tanggal', fn (RedeemTransactionDetail $d) => $d->redeemTransaction->redeemed_at->format('d/m/Y H:i'))
            ->addColumn('transaction_code', fn (RedeemTransactionDetail $d) => $d->redeemTransaction->transaction_code)
            ->addColumn('kasir', fn (RedeemTransactionDetail $d) => $d->redeemTransaction->user->name ?? '-')
            ->addColumn('total_ticket_scanned', fn (RedeemTransactionDetail $d) => (int) $d->redeemTransaction->total_ticket_scanned)
            ->addColumn('remaining_ticket', fn (RedeemTransactionDetail $d) => max(0, (int) $d->redeemTransaction->total_ticket_scanned - (int) $d->redeemTransaction->total_ticket_used))
            ->make(true);
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(
            new RedeemHistoryExport($this->filterParams($request)),
            'history-redeem-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $query = RedeemTransactionDetail::with(['redeemTransaction.user']);
        $this->applyFilters($query, $request);
        $details = $query->latest()->get();

        $pdf = Pdf::loadView('exports.redeem-history-pdf', compact('details'))->setPaper('a4', 'landscape');

        return $pdf->download('history-redeem-'.now()->format('Ymd-His').'.pdf');
    }

    protected function applyFilters($query, Request $request): void
    {
        $filters = $this->filterParams($request);

        if (! $request->user()->isSuperAdmin()) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->where('store_id', $request->user()->store_id));
        } elseif (! empty($filters['store_id'])) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->where('store_id', $filters['store_id']));
        }

        if (! empty($filters['date_from'])) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->whereDate('redeemed_at', '>=', $filters['date_from']));
        }
        if (! empty($filters['date_to'])) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->whereDate('redeemed_at', '<=', $filters['date_to']));
        }
        if (! empty($filters['user_id'])) {
            $query->whereHas('redeemTransaction', fn ($q) => $q->where('user_id', $filters['user_id']));
        }
        if (! empty($filters['name'])) {
            $query->where('item_name', 'like', '%'.$filters['name'].'%');
        }
        if (! empty($filters['barcode'])) {
            $query->where('item_barcode', 'like', '%'.$filters['barcode'].'%');
        }
    }

    protected function filterParams(Request $request): array
    {
        return $request->only(['date_from', 'date_to', 'user_id', 'store_id', 'name', 'barcode']);
    }
}
