<?php

namespace App\Http\Controllers;

use App\Exports\ItemImportTemplateExport;
use App\Exports\ItemsExport;
use App\Http\Requests\Item\ImportItemRequest;
use App\Http\Requests\Item\StoreItemRequest;
use App\Http\Requests\Item\UpdateItemRequest;
use App\Imports\ItemsImport;
use App\Models\Item;
use App\Models\Store;
use App\Services\ItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class ItemController extends Controller implements HasMiddleware
{
    public function __construct(protected ItemService $itemService)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,App\Models\Item', only: ['index', 'data']),
            new Middleware('can:create,App\Models\Item', only: ['create', 'store']),
            new Middleware('can:update,item', only: ['edit', 'update']),
            new Middleware('can:delete,item', only: ['destroy']),
            new Middleware('can:import,App\Models\Item', only: ['showImportForm', 'import', 'downloadTemplate']),
        ];
    }

    public function index(): View
    {
        return view('items.index', [
            'allocations' => Item::ALLOCATIONS,
            'categories' => Item::CATEGORIES,
            'subCategories' => Item::SUB_CATEGORIES,
            'stores' => $this->availableStores(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Item::query()->with('store');

        if (! $request->user()->isSuperAdmin()) {
            $query->where('store_id', $request->user()->store_id);
        } elseif ($request->filled('store_id')) {
            $query->where('store_id', $request->integer('store_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('allocation')) {
            $query->where('allocation', $request->allocation);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        if ($request->filled('low_stock')) {
            $query->lowStock();
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('stock_badge', function (Item $item) {
                $class = $item->isLowStock() ? 'text-bg-danger' : 'text-bg-success';

                return "<span class=\"badge {$class}\">{$item->stock}</span>";
            })
            ->addColumn('store_label', fn (Item $item) => $item->store ? "{$item->store->code} - {$item->store->name}" : '-')
            ->addColumn('status_badge', function (Item $item) {
                return $item->is_active
                    ? '<span class="badge text-bg-success">Aktif</span>'
                    : '<span class="badge text-bg-secondary">Nonaktif</span>';
            })
            ->addColumn('actions', function (Item $item) {
                return view('items._actions', compact('item'))->render();
            })
            ->rawColumns(['stock_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function create(): View
    {
        return view('items.form', [
            'item' => new Item(),
            'allocations' => Item::ALLOCATIONS,
            'categories' => Item::CATEGORIES,
            'subCategories' => Item::SUB_CATEGORIES,
            'stores' => $this->availableStores(),
        ]);
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        $this->itemService->create($request->validated());

        return redirect()->route('items.index')->with('success', 'Item berhasil ditambahkan.');
    }

    public function edit(Item $item): View
    {
        return view('items.form', [
            'item' => $item,
            'allocations' => Item::ALLOCATIONS,
            'categories' => Item::CATEGORIES,
            'subCategories' => Item::SUB_CATEGORIES,
            'stores' => $this->availableStores(),
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $this->itemService->update($item, $request->validated());

        return redirect()->route('items.index')->with('success', 'Item berhasil diperbarui.');
    }

    public function destroy(Item $item): JsonResponse
    {
        $this->itemService->delete($item);

        return response()->json(['message' => 'Item berhasil dihapus.']);
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(
            new ItemsExport($request->get('search'), $request->only(['category', 'allocation', 'store_id'])),
            'master-item-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportCsv(Request $request)
    {
        return Excel::download(
            new ItemsExport($request->get('search'), $request->only(['category', 'allocation', 'store_id'])),
            'master-item-'.now()->format('Ymd-His').'.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    public function exportPdf(Request $request)
    {
        $query = Item::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $items = $query->latest()->get();

        $pdf = Pdf::loadView('exports.items-pdf', compact('items'))->setPaper('a4', 'landscape');

        return $pdf->download('master-item-'.now()->format('Ymd-His').'.pdf');
    }

    public function showImportForm(): View
    {
        return view('items.import');
    }

    public function downloadTemplate()
    {
        return Excel::download(new ItemImportTemplateExport(), 'template-import-item.xlsx');
    }

    public function import(ImportItemRequest $request): RedirectResponse
    {
        $import = app(ItemsImport::class);

        try {
            Excel::import($import, $request->file('file'));

            $message = "{$import->successCount} item berhasil diimport.";
            if (! empty($import->errors)) {
                $message .= ' '.count($import->errors).' baris dilewati (lihat detail di log aktivitas).';
            }

            return redirect()->route('items.index')->with('success', $message);
        } catch (Throwable $e) {
            return redirect()->route('items.import.form')->withErrors([
                'file' => 'Import gagal: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Tambah item cepat dari popup Stock Opname / Redeem saat barcode belum terdaftar.
     */
    public function quickStore(Request $request): JsonResponse
    {
        if (! $request->user()->canWrite()) {
            return response()->json(['message' => 'Anda tidak memiliki hak untuk menambah item.'], 403);
        }

        $data = $request->validate([
            'barcode' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $item = $this->itemService->create([
            'barcode' => $data['barcode'],
            'store_id' => $request->user()->store_id ?? Store::where('code', 'S040')->value('id'),
            'name' => $data['name'],
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 0,
            'ticket_redeem_qty' => 1,
            'minimum_stock' => 5,
            'stock' => $data['stock'] ?? 0,
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Item baru berhasil ditambahkan. Lengkapi Category/Allocation-nya lewat menu Master Item.', 'item' => $item]);
    }

    protected function availableStores()
    {
        return Store::active()
            ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->whereKey(auth()->user()->store_id))
            ->orderBy('code')->get();
    }
}
