<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        return view('activity-logs.index', [
            'users' => User::orderBy('name')->get(),
            'modules' => ActivityLog::query()->distinct()->pluck('module'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = ActivityLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('waktu', fn (ActivityLog $log) => $log->created_at->format('d/m/Y H:i:s'))
            ->addColumn('user_name', fn (ActivityLog $log) => $log->user->name ?? 'System')
            ->addColumn('action_badge', function (ActivityLog $log) {
                $map = [
                    'create' => 'text-bg-success', 'update' => 'text-bg-warning',
                    'delete' => 'text-bg-danger', 'login' => 'text-bg-info',
                    'logout' => 'text-bg-secondary', 'redeem' => 'text-bg-primary',
                ];
                $class = $map[$log->action] ?? 'text-bg-secondary';

                return "<span class=\"badge {$class}\">{$log->action}</span>";
            })
            ->rawColumns(['action_badge'])
            ->orderColumn('waktu', 'created_at $1')
            ->make(true);
    }
}
