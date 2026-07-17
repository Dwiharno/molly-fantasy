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

        $stats = [
            'total_item' => Item::count(),
            'redeem_today' => RedeemTransaction::whereDate('redeemed_at', $today)->count(),
            'opname_today' => StockOpname::whereDate('opname_date', $today)->count(),
            'ticket_redeemed_today' => (int) RedeemTransaction::whereDate('redeemed_at', $today)->sum('total_ticket_used'),
        ];

        $topHadiah = RedeemTransactionDetail::select('item_id', 'item_name')
            ->selectRaw('SUM(qty) as total_qty')
            ->groupBy('item_id', 'item_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        $redeemChart = RedeemTransaction::selectRaw('DATE(redeemed_at) as tanggal, COUNT(*) as total')
            ->where('redeemed_at', '>=', now()->subDays(13))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $opnameChart = StockOpname::selectRaw('DATE(opname_date) as tanggal, COUNT(*) as total')
            ->where('opname_date', '>=', now()->subDays(13))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $barangMasukChart = DB::table('item_stock_movements')
            ->selectRaw('DATE(created_at) as tanggal, SUM(quantity) as total')
            ->where('type', 'in')
            ->where('created_at', '>=', now()->subDays(13))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $barangKeluarChart = DB::table('item_stock_movements')
            ->selectRaw('DATE(created_at) as tanggal, SUM(quantity) as total')
            ->whereIn('type', ['out', 'redeem'])
            ->where('created_at', '>=', now()->subDays(13))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $recentActivities = ActivityLog::with('user')->latest()->limit(15)->get();

        return view('dashboard.index', compact(
            'stats', 'topHadiah', 'redeemChart', 'opnameChart',
            'barangMasukChart', 'barangKeluarChart', 'recentActivities'
        ));
    }
}
