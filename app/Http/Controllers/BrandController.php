<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Repositories\BrandRepository;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BrandController extends Controller implements HasMiddleware
{
    public function __construct(
        protected BrandRepository $brandRepository,
        protected ActivityLogService $activityLog
    ) {
    }

    public static function middleware(): array
    {
        return [new Middleware('can_write', except: ['index', 'data'])];
    }

    public function index(): View
    {
        return view('brands.index');
    }

    public function data(): JsonResponse
    {
        return DataTables::of($this->brandRepository->newQuery()->withCount('items'))
            ->addIndexColumn()
            ->addColumn('status_badge', fn (Brand $b) => $b->is_active
                ? '<span class="badge text-bg-success">Aktif</span>'
                : '<span class="badge text-bg-secondary">Nonaktif</span>')
            ->addColumn('actions', fn (Brand $b) => view('brands._actions', ['brand' => $b])->render())
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:brands,name']]);
        $data['is_active'] = $request->boolean('is_active', true);

        $brand = $this->brandRepository->create($data);
        $this->activityLog->log(Auth::user(), 'create', 'master_brand', "Menambahkan brand: {$brand->name}");

        return response()->json(['message' => 'Brand berhasil ditambahkan.']);
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('brands', 'name')->ignore($brand->id)],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $brand->update($data);
        $this->activityLog->log(Auth::user(), 'update', 'master_brand', "Mengubah brand: {$brand->name}");

        return response()->json(['message' => 'Brand berhasil diperbarui.']);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        if ($brand->items()->exists()) {
            return response()->json(['message' => 'Brand tidak dapat dihapus karena masih digunakan oleh item.'], 422);
        }

        $name = $brand->name;
        $brand->delete();
        $this->activityLog->log(Auth::user(), 'delete', 'master_brand', "Menghapus brand: {$name}");

        return response()->json(['message' => 'Brand berhasil dihapus.']);
    }
}
