<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Item;
use App\Models\RedeemTransaction;
use App\Models\RedeemTransactionDetail;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();
        $storeId = auth()->user()->isSuperAdmin() ? null : auth()->user()->store_id;
        $inventoryStoreId = auth()->user()->canViewAllStoreStock() ? null : auth()->user()->store_id;

        $stats = [
            'total_item' => Item::when($inventoryStoreId, fn ($q) => $q->where('store_id', $inventoryStoreId))->count(),
            'out_of_stock' => Item::when($inventoryStoreId, fn ($q) => $q->where('store_id', $inventoryStoreId))->where('stock', '<=', 0)->count(),
            'total_inventory_value' => (float) Item::when($inventoryStoreId, fn ($q) => $q->where('store_id', $inventoryStoreId))
                ->selectRaw('COALESCE(SUM(selling_price * stock), 0) AS value')->value('value'),
            'total_redeem_value' => (float) RedeemTransaction::when($storeId, fn ($q) => $q->where('store_id', $storeId))->sum('total_value'),
            'redeem_today' => RedeemTransaction::when($storeId, fn ($q) => $q->where('store_id', $storeId))->whereDate('redeemed_at', $today)->count(),
            'opname_today' => StockOpname::when($storeId, fn ($q) => $q->where('store_id', $storeId))->whereDate('opname_date', $today)->count(),
            'ticket_redeemed_today' => (int) RedeemTransaction::when($storeId, fn ($q) => $q->where('store_id', $storeId))->whereDate('redeemed_at', $today)->sum('total_ticket_used'),
        ];

        $topHadiah = RedeemTransactionDetail::select('item_id', 'item_name')
            ->when($storeId, fn ($q) => $q->whereHas('redeemTransaction', fn ($t) => $t->where('store_id', $storeId)))
            ->selectRaw('SUM(qty) as total_qty')
            ->groupBy('item_id', 'item_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        $redeemChart = RedeemTransaction::selectRaw('DATE(redeemed_at) as tanggal, COUNT(*) as total')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->where('redeemed_at', '>=', now()->subDays(13))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $opnameChart = StockOpname::selectRaw('DATE(opname_date) as tanggal, COUNT(*) as total')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->where('opname_date', '>=', now()->subDays(13))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $barangMasukChart = DB::table('item_stock_movements')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->selectRaw('DATE(created_at) as tanggal, SUM(quantity) as total')
            ->where('type', 'in')
            ->where('created_at', '>=', now()->subDays(13))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $barangKeluarChart = DB::table('item_stock_movements')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->selectRaw('DATE(created_at) as tanggal, SUM(quantity) as total')
            ->whereIn('type', ['out', 'redeem'])
            ->where('created_at', '>=', now()->subDays(13))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $recentActivities = ActivityLog::with('user')
            ->when($storeId, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('store_id', $storeId)))
            ->latest()->limit(15)->get();

        return view('dashboard.index', compact(
            'stats', 'topHadiah', 'redeemChart', 'opnameChart',
            'barangMasukChart', 'barangKeluarChart', 'recentActivities'
        ));
    }
}
