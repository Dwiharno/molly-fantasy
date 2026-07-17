<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Repositories\StoreRepository;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class StoreController extends Controller implements HasMiddleware
{
    public function __construct(
        protected StoreRepository $storeRepository,
        protected ActivityLogService $activityLog
    ) {
    }

    public static function middleware(): array
    {
        return [new Middleware('can_write', except: ['index', 'data'])];
    }

    public function index(): View
    {
        return view('stores.index');
    }

    public function data(): JsonResponse
    {
        $query = $this->storeRepository->newQuery()->withCount(['items', 'users']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status_badge', fn (Store $s) => $s->is_active
                ? '<span class="badge text-bg-success">Aktif</span>'
                : '<span class="badge text-bg-secondary">Nonaktif</span>')
            ->addColumn('actions', fn (Store $s) => view('stores._actions', ['store' => $s])->render())
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:stores,code'],
            'name' => ['required', 'string', 'max:150'],
            'account_name' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $storeModel = $this->storeRepository->create($data);
        $this->activityLog->log(Auth::user(), 'create', 'master_store', "Menambahkan outlet: {$storeModel->name}");

        return response()->json(['message' => 'Outlet berhasil ditambahkan.']);
    }

    public function update(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('stores', 'code')->ignore($store->id)],
            'name' => ['required', 'string', 'max:150'],
            'account_name' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $store->update($data);
        $this->activityLog->log(Auth::user(), 'update', 'master_store', "Mengubah outlet: {$store->name}");

        return response()->json(['message' => 'Outlet berhasil diperbarui.']);
    }

    public function destroy(Store $store): JsonResponse
    {
        if ($store->items()->exists()) {
            return response()->json(['message' => 'Outlet tidak dapat dihapus karena masih memiliki item.'], 422);
        }

        $name = $store->name;
        $store->delete();
        $this->activityLog->log(Auth::user(), 'delete', 'master_store', "Menghapus outlet: {$name}");

        return response()->json(['message' => 'Outlet berhasil dihapus.']);
    }
}
