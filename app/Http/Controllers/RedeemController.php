<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\RedeemTransaction;
use App\Services\RedeemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RedeemController extends Controller implements HasMiddleware
{
    public function __construct(protected RedeemService $redeemService)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('can_write'),
        ];
    }

    public function index(): View
    {
        $posStates = collect(RedeemService::AVAILABLE_POS)
            ->mapWithKeys(fn ($pos) => [$pos => $this->redeemService->getState($pos)]);

        return view('redeem.index', [
            'posList' => RedeemService::AVAILABLE_POS,
            'posStates' => $posStates,
        ]);
    }

    protected function resolvePos(Request $request): int
    {
        $pos = (int) $request->input('pos', 1);

        return in_array($pos, RedeemService::AVAILABLE_POS, true) ? $pos : 1;
    }

    public function scanTicket(Request $request): JsonResponse
    {
        $request->validate(['barcode' => ['required', 'string']]);
        $pos = $this->resolvePos($request);

        try {
            $result = $this->redeemService->scanTicket($pos, $request->barcode);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $result['ticket'] = $this->formatTicketForResponse($result['scan_id']);

        return response()->json($result);
    }

    public function deleteTicket(Request $request, int $scanId): JsonResponse
    {
        $pos = $this->resolvePos($request);

        try {
            $result = $this->redeemService->deleteScannedTicket($pos, $scanId);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(array_merge(['message' => 'Tiket berhasil dihapus.'], $result));
    }

    public function resetTickets(Request $request): JsonResponse
    {
        $pos = $this->resolvePos($request);
        $result = $this->redeemService->resetScannedTickets($pos);

        return response()->json(array_merge(['message' => 'Seluruh sesi redeem pada Pos ini berhasil direset.'], $result));
    }

    public function addManualTicket(Request $request): JsonResponse
    {
        $request->validate(['value' => ['required', 'integer', 'min:1']]);
        $pos = $this->resolvePos($request);

        try {
            $result = $this->redeemService->addManualTicket($pos, (int) $request->value);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $result['ticket'] = $this->formatTicketForResponse($result['scan_id']);

        return response()->json($result);
    }

    protected function formatTicketForResponse(int $scanId): ?array
    {
        $scan = \App\Models\RedeemTicketScan::find($scanId);

        if (! $scan) {
            return null;
        }

        return [
            'id' => $scan->id,
            'ticket_barcode' => $scan->ticket_barcode,
            'ticket_code' => $scan->ticket_code_5digit,
            'value' => (int) $scan->ticket_code_5digit,
            'scanned_at' => $scan->scanned_at->format('H:i:s'),
            'is_manual' => str_starts_with($scan->ticket_barcode, 'MANUAL-'),
        ];
    }

    public function scanItem(Request $request): JsonResponse
    {
        $request->validate([
            'barcode' => ['required', 'string'],
            'qty' => ['nullable', 'integer', 'min:1'],
        ]);
        $pos = $this->resolvePos($request);

        try {
            $result = $this->redeemService->scanItem($pos, $request->barcode, (int) $request->get('qty', 1));
        } catch (ValidationException $e) {
            $errors = $e->errors();

            if (isset($errors['barcode'])) {
                return response()->json(['code' => 'NOT_FOUND', 'message' => 'Barcode hadiah tidak ditemukan.'], 404);
            }
            if (isset($errors['ticket'])) {
                return response()->json(['code' => 'INSUFFICIENT_TICKET', 'message' => 'Tiket Tidak Mencukupi'], 422);
            }
            if (isset($errors['stock'])) {
                return response()->json(['code' => 'OUT_OF_STOCK', 'message' => 'Stock Habis'], 422);
            }

            return response()->json(['message' => 'Terjadi kesalahan saat memproses redeem.'], 422);
        }

        return response()->json([
            'message' => "Berhasil redeem {$result['item']->name}.",
            'item' => ['id' => $result['item']->id, 'name' => $result['item']->name, 'barcode' => $result['item']->barcode],
            'detail' => [
                'qty' => $result['detail']->qty,
                'ticket_used' => $result['detail']->ticket_used,
            ],
            'pool' => $result['pool'],
            'total_used' => $result['total_used'],
            'transaction_code' => $result['transaction']->transaction_code,
        ]);
    }

    public function updateQty(Request $request): JsonResponse
    {
        $request->validate([
            'barcode' => ['required', 'string'],
            'qty' => ['required', 'integer', 'min:1'],
        ]);

        $pos = $this->resolvePos($request);

        try {
            $result = $this->redeemService->updateItemQty($pos, $request->barcode, (int) $request->qty);
        } catch (ValidationException $e) {
            $errors = $e->errors();

            if (isset($errors['barcode'])) {
                return response()->json(['code' => 'NOT_FOUND', 'message' => 'Barcode hadiah tidak ditemukan.'], 404);
            }
            if (isset($errors['ticket'])) {
                return response()->json(['code' => 'INSUFFICIENT_TICKET', 'message' => 'Tiket Tidak Mencukupi'], 422);
            }
            if (isset($errors['stock'])) {
                return response()->json(['code' => 'OUT_OF_STOCK', 'message' => 'Stock Habis'], 422);
            }

            return response()->json(['message' => 'Terjadi kesalahan saat mengubah qty redeem.'], 422);
        }

        return response()->json([
            'message' => "Qty {$result['item']->name} berhasil diubah.",
            'item' => ['id' => $result['item']->id, 'name' => $result['item']->name, 'barcode' => $result['item']->barcode],
            'detail' => [
                'qty' => $result['detail']->qty,
                'ticket_used' => $result['detail']->ticket_used,
            ],
            'pool' => $result['pool'],
            'total_used' => $result['total_used'],
            'transaction_code' => $result['transaction']->transaction_code,
        ]);
    }

    public function removeItem(Request $request): JsonResponse
    {
        $request->validate([
            'barcode' => ['required', 'string'],
        ]);

        $pos = $this->resolvePos($request);

        try {
            $result = $this->redeemService->removeItemFromCart($pos, $request->barcode);
        } catch (ValidationException $e) {
            $errors = $e->errors();

            if (isset($errors['barcode'])) {
                return response()->json(['code' => 'NOT_FOUND', 'message' => 'Barcode hadiah tidak ditemukan.'], 404);
            }

            return response()->json(['message' => 'Terjadi kesalahan saat menghapus item dari keranjang.'], 422);
        }

        return response()->json([
            'message' => "Item {$result['item']->name} dihapus dari keranjang.",
            'item' => ['id' => $result['item']->id, 'name' => $result['item']->name, 'barcode' => $result['item']->barcode],
            'pool' => $result['pool'],
            'total_used' => $result['total_used'],
            'transaction_code' => $result['transaction']->transaction_code,
        ]);
    }

    public function finish(Request $request): JsonResponse
    {
        $pos = $this->resolvePos($request);
        $transaction = $this->redeemService->finishTransaction($pos);

        if (! $transaction) {
            return response()->json(['message' => 'Belum ada transaksi redeem pada Pos ini.'], 422);
        }

        return response()->json([
            'message' => "Transaksi {$transaction->transaction_code} selesai.",
            'print_url' => route('redeem.struk', $transaction),
        ]);
    }

    public function struk(RedeemTransaction $transaction): View
    {
        $transaction->load(['details', 'user']);

        return view('redeem.struk', compact('transaction'));
    }

    /**
     * Pencarian barcode item untuk panel "Cari & Copy Barcode Manual",
     * dipakai saat scanner tidak bisa membaca barcode fisik pada barang.
     */
    public function searchItems(Request $request): JsonResponse
    {
        $term = trim((string) $request->get('q', ''));

        $query = Item::query()->active()->select('id', 'barcode', 'name', 'stock');

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            });
        }

        $items = $query->orderBy('name')->limit(20)->get();

        return response()->json(['items' => $items]);
    }
}
