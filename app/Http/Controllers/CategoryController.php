<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CategoryController extends Controller implements HasMiddleware
{
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected ActivityLogService $activityLog
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('can_write', except: ['index', 'data']),
        ];
    }

    public function index(): View
    {
        return view('categories.index');
    }

    public function data(): JsonResponse
    {
        $query = $this->categoryRepository->newQuery()->withCount(['subCategories', 'items']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status_badge', fn (Category $c) => $c->is_active
                ? '<span class="badge text-bg-success">Aktif</span>'
                : '<span class="badge text-bg-secondary">Nonaktif</span>')
            ->addColumn('actions', fn (Category $c) => view('categories._actions', ['category' => $c])->render())
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', 'unique:categories,code'],
            'description' => ['nullable', 'string'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $category = $this->categoryRepository->create($data);
        $this->activityLog->log(Auth::user(), 'create', 'master_kategori', "Menambahkan kategori: {$category->name}");

        return $request->wantsJson()
            ? response()->json(['message' => 'Kategori berhasil ditambahkan.'])
            : back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', Rule::unique('categories', 'code')->ignore($category->id)],
            'description' => ['nullable', 'string'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $category->update($data);
        $this->activityLog->log(Auth::user(), 'update', 'master_kategori', "Mengubah kategori: {$category->name}");

        return $request->wantsJson()
            ? response()->json(['message' => 'Kategori berhasil diperbarui.'])
            : back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->items()->exists()) {
            return response()->json(['message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh item.'], 422);
        }

        $name = $category->name;
        $category->delete();
        $this->activityLog->log(Auth::user(), 'delete', 'master_kategori', "Menghapus kategori: {$name}");

        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }
}
