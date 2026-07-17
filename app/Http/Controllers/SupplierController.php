<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Repositories\SupplierRepository;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller implements HasMiddleware
{
    public function __construct(
        protected SupplierRepository $supplierRepository,
        protected ActivityLogService $activityLog
    ) {
    }

    public static function middleware(): array
    {
        return [new Middleware('can_write', except: ['index', 'data'])];
    }

    public function index(): View
    {
        return view('suppliers.index');
    }

    public function data(): JsonResponse
    {
        return DataTables::of($this->supplierRepository->newQuery()->withCount('items'))
            ->addIndexColumn()
            ->addColumn('status_badge', fn (Supplier $s) => $s->is_active
                ? '<span class="badge text-bg-success">Aktif</span>'
                : '<span class="badge text-bg-secondary">Nonaktif</span>')
            ->addColumn('actions', fn (Supplier $s) => view('suppliers._actions', ['supplier' => $s])->render())
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $supplier = $this->supplierRepository->create($data);
        $this->activityLog->log(Auth::user(), 'create', 'master_supplier', "Menambahkan supplier: {$supplier->name}");

        return response()->json(['message' => 'Supplier berhasil ditambahkan.']);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $supplier->update($data);
        $this->activityLog->log(Auth::user(), 'update', 'master_supplier', "Mengubah supplier: {$supplier->name}");

        return response()->json(['message' => 'Supplier berhasil diperbarui.']);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        if ($supplier->items()->exists()) {
            return response()->json(['message' => 'Supplier tidak dapat dihapus karena masih digunakan oleh item.'], 422);
        }

        $name = $supplier->name;
        $supplier->delete();
        $this->activityLog->log(Auth::user(), 'delete', 'master_supplier', "Menghapus supplier: {$name}");

        return response()->json(['message' => 'Supplier berhasil dihapus.']);
    }
}
