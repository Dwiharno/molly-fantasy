<?php

namespace App\Http\Controllers;

use App\Exports\StockOpnameExport;
use App\Http\Requests\StockOpname\ScanBarcodeRequest;
use App\Models\StockOpname;
use App\Services\BeritaAcaraService;
use App\Services\StockOpnameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Yajra\DataTables\Facades\DataTables;

class StockOpnameController extends Controller implements HasMiddleware
{
    public function __construct(
        protected StockOpnameService $stockOpnameService,
        protected BeritaAcaraService $beritaAcaraService,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('can:create,App\Models\StockOpname', only: ['start']),
            new Middleware('can:update,opname', only: ['scanPage', 'scan', 'undo', 'reset', 'complete']),
            new Middleware('can:delete,opname', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('stock-opname.index');
    }

    public function data(): JsonResponse
    {
        $query = StockOpname::with('user')->withCount('details');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status_badge', function (StockOpname $o) {
                $map = [
                    'draft' => 'text-bg-secondary',
                    'in_progress' => 'text-bg-warning',
                    'completed' => 'text-bg-success',
                ];
                $label = ['draft' => 'Draft', 'in_progress' => 'Berlangsung', 'completed' => 'Selesai'][$o->status] ?? $o->status;

                return "<span class=\"badge {$map[$o->status]}\">{$label}</span>";
            })
            ->addColumn('actions', fn (StockOpname $o) => view('stock-opname._actions', ['opname' => $o])->render())
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function start(Request $request): RedirectResponse
    {
        $opname = $this->stockOpnameService->startSession($request->get('notes'));

        return redirect()->route('stock-opname.scan', $opname);
    }

    public function scanPage(StockOpname $opname): View
    {
        $opname->load(['details.item']);

        return view('stock-opname.scan', compact('opname'));
    }

    public function scan(ScanBarcodeRequest $request, StockOpname $opname): JsonResponse
    {
        try {
            $result = $this->stockOpnameService->scan($opname, $request->validated()['barcode']);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($result['status'] === 'not_found') {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Barcode tidak ditemukan di Master Item.',
                'barcode' => $result['barcode'],
            ], 404);
        }

        return response()->json([
            'status' => 'found',
            'item' => [
                'id' => $result['item']->id,
                'barcode' => $result['item']->barcode,
                'name' => $result['item']->name,
            ],
            'detail' => [
                'expected_stock' => $result['detail']->expected_stock,
                'actual_stock' => $result['detail']->actual_stock,
            ],
            'totals' => $this->totals($opname),
        ]);
    }

    public function undo(Request $request, StockOpname $opname): JsonResponse
    {
        $request->validate(['item_id' => ['required', 'integer']]);

        $this->stockOpnameService->undoScan($opname, (int) $request->item_id);

        return response()->json(['message' => 'Scan terakhir dibatalkan.', 'totals' => $this->totals($opname)]);
    }

    public function reset(StockOpname $opname): JsonResponse
    {
        $this->stockOpnameService->resetScan($opname);

        return response()->json(['message' => 'Seluruh scan pada sesi ini telah direset.']);
    }

    public function complete(StockOpname $opname): JsonResponse
    {
        $opname = $this->stockOpnameService->complete($opname);

        return response()->json([
            'message' => "Stock opname {$opname->code} berhasil disimpan dan stok telah disesuaikan.",
            'redirect' => route('stock-opname.show', $opname),
        ]);
    }

    public function show(StockOpname $opname): View
    {
        $opname->load(['details.item', 'user']);

        return view('stock-opname.show', compact('opname'));
    }

    public function generateBeritaAcara(StockOpname $opname)
    {
        $path = $this->beritaAcaraService->generate($opname);

        return Storage::disk('public')->download($path, "berita-acara-{$opname->code}.pdf");
    }

    public function exportExcel(StockOpname $opname)
    {
        return Excel::download(new StockOpnameExport($opname), "stock-opname-{$opname->code}.xlsx");
    }

    public function exportPdf(StockOpname $opname)
    {
        $opname->load(['details.item', 'user']);
        $pdf = Pdf::loadView('exports.stock-opname-pdf', compact('opname'))->setPaper('a4', 'portrait');

        return $pdf->download("stock-opname-{$opname->code}.pdf");
    }

    public function destroy(StockOpname $opname): JsonResponse
    {
        $opname->delete();

        return response()->json(['message' => 'Sesi stock opname berhasil dihapus.']);
    }

    protected function totals(StockOpname $opname): array
    {
        $opname->loadCount('details');
        $sums = $opname->details()->selectRaw('SUM(expected_stock) as expected, SUM(actual_stock) as actual')->first();

        return [
            'total_scanned_items' => $opname->details_count,
            'total_expected' => (int) ($sums->expected ?? 0),
            'total_actual' => (int) ($sums->actual ?? 0),
        ];
    }
}
