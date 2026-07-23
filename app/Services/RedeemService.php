<?php

namespace App\Services;

use App\Jobs\SyncToGoogleSheetsJob;
use App\Models\RedeemTicketScan;
use App\Models\RedeemTransaction;
use App\Notifications\RedeemFailedNotification;
use App\Notifications\RedeemSuccessNotification;
use App\Repositories\Contracts\ItemRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class RedeemService
{
    public const AVAILABLE_POS = [1, 2, 3, 4];
    public const MEMBER_POS = 4;

    public function __construct(
        protected TicketBarcodeService $ticketParser,
        protected ItemRepositoryInterface $itemRepository,
        protected ActivityLogService $activityLog,
    ) {
    }

    /**
     * Ambil state lengkap (pool, total discan, total terpakai, cart) untuk satu Pos.
     * Dipakai untuk menampilkan/refresh tampilan setiap tab Pos.
     */
    public function getState(int $pos): array
    {
        return [
            'pos' => $pos,
            'pool' => $this->currentPool($pos),
            'total_scanned_value' => (int) Session::get($this->key($pos, 'total_scanned_value'), 0),
            'total_used' => (int) Session::get($this->key($pos, 'total_used'), 0),
            'cart' => $this->currentCartDetails($pos),
            'scanned_tickets' => $this->listScannedTickets($pos),
            'member_phone' => Session::get($this->key($pos, 'member_phone'), ''),
        ];
    }

    public function currentPool(int $pos): int
    {
        return (int) Session::get($this->key($pos, 'pool'), 0);
    }

    public function currentCartDetails(int $pos)
    {
        $transactionId = Session::get($this->key($pos, 'transaction_id'));

        if (! $transactionId) {
            return collect();
        }

        return RedeemTransaction::find($transactionId)?->details ?? collect();
    }

    /**
     * Scan satu tiket untuk Pos tertentu. Nilai tiket ditambahkan ke pool Pos tersebut.
     * State Pos lain tidak terpengaruh sama sekali (disimpan terpisah per Pos).
     */
    public function scanTicket(int $pos, string $barcode): array
    {
        $this->rejectMemberTicketScan($pos);
        $parsed = $this->ticketParser->parse($barcode);

        if ($parsed['value'] < 1 || $parsed['value'] > 99999) {
            throw ValidationException::withMessages([
                'barcode' => 'Nilai tiket tidak valid untuk audit redeem.',
            ]);
        }

        $scan = RedeemTicketScan::create([
            'redeem_transaction_id' => null,
            'ticket_barcode' => $barcode,
            'ticket_code_5digit' => $parsed['code'],
            'is_used' => false,
            'user_id' => Auth::id(),
            'scanned_at' => now(),
        ]);

        $ids = Session::get($this->key($pos, 'scanned_ticket_ids'), []);
        $ids[] = $scan->id;
        Session::put($this->key($pos, 'scanned_ticket_ids'), $ids);

        $pool = $this->currentPool($pos) + $parsed['value'];
        Session::put($this->key($pos, 'pool'), $pool);

        $totalScannedValue = (int) Session::get($this->key($pos, 'total_scanned_value'), 0) + $parsed['value'];
        Session::put($this->key($pos, 'total_scanned_value'), $totalScannedValue);

        return [
            'scan_id' => $scan->id,
            'ticket_value' => $parsed['value'],
            'ticket_code' => $parsed['code'],
            'pool' => $pool,
            'total_scanned_value' => $totalScannedValue,
        ];
    }

    /**
     * Scan barcode hadiah untuk Pos tertentu. Jika tiket cukup & stok tersedia, redeem otomatis.
     *
     * @throws ValidationException jika tiket tidak cukup atau stok habis
     */
    public function scanItem(int $pos, string $barcode, int $qty = 1): array
    {
        $item = $this->itemRepository->findByBarcode($barcode);

        if (! $item) {
            throw ValidationException::withMessages(['barcode' => 'NOT_FOUND']);
        }

        if ($qty < 1) {
            throw ValidationException::withMessages(['qty' => 'Kuantitas harus lebih dari 0.']);
        }

        $pool = $this->currentPool($pos);
        $requiredTickets = $item->ticket_redeem_qty * $qty;

        if ($pool < $requiredTickets) {
            Auth::user()?->notify(new RedeemFailedNotification('Tiket tidak mencukupi', $barcode));

            throw ValidationException::withMessages(['ticket' => 'INSUFFICIENT_TICKET']);
        }

        if ($item->stock < $qty) {
            Auth::user()?->notify(new RedeemFailedNotification('Stock habis', $barcode));

            throw ValidationException::withMessages(['stock' => 'OUT_OF_STOCK']);
        }

        return DB::transaction(function () use ($pos, $item, $qty, $requiredTickets, $pool) {
            $transaction = $this->getOrCreateActiveTransaction($pos);
            $detail = $transaction->details()->where('item_id', $item->id)->first();

            $stockBefore = $item->stock;
            $this->itemRepository->decrementStock($item, $qty, [
                'type' => 'redeem',
                'reference_type' => RedeemTransaction::class,
                'reference_id' => $transaction->id,
                'notes' => "Redeem hadiah transaksi {$transaction->transaction_code} (Pos {$pos})",
            ]);
            $item->refresh();

            if ($detail) {
                $newQty = (int) $detail->qty + $qty;
                $detail->update([
                    'qty' => $newQty,
                    'ticket_used' => $item->ticket_redeem_qty * $newQty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $item->stock,
                ]);
            } else {
                $detail = $transaction->details()->create([
                    'item_id' => $item->id,
                    'item_barcode' => $item->barcode,
                    'item_name' => $item->name,
                    'qty' => $qty,
                    'ticket_used' => $requiredTickets,
                    'stock_before' => $stockBefore,
                    'stock_after' => $item->stock,
                ]);
            }

            $this->consumeTicketsFifo($pos, $requiredTickets, $transaction->id);
            $this->syncTransactionTotals($transaction);

            $newPool = $pool - $requiredTickets;
            Session::put($this->key($pos, 'pool'), $newPool);

            $totalUsed = (int) $transaction->fresh()->total_ticket_used;
            Session::put($this->key($pos, 'total_used'), $totalUsed);

            SyncToGoogleSheetsJob::dispatch('redeem', [
                $transaction->transaction_code,
                now()->format('Y-m-d H:i:s'),
                Auth::user()?->name ?? '-',
                $item->barcode,
                $item->name,
                $qty,
                $requiredTickets,
            ]);

            $this->activityLog->log(
                Auth::user(), 'redeem', 'redeem_hadiah',
                "Redeem {$item->name} ({$qty}x) menggunakan {$requiredTickets} tiket di Pos {$pos}."
            );

            Auth::user()?->notify(new RedeemSuccessNotification($transaction, $item->name, $requiredTickets));

            return [
                'item' => $item,
                'detail' => $detail->fresh(),
                'pool' => $newPool,
                'total_used' => $totalUsed,
                'transaction' => $transaction->fresh(),
            ];
        });
    }

    public function updateItemQty(int $pos, string $barcode, int $qty): array
    {
        if ($qty < 1) {
            throw ValidationException::withMessages(['qty' => 'Kuantitas harus lebih dari 0.']);
        }

        $item = $this->itemRepository->findByBarcode($barcode);

        if (! $item) {
            throw ValidationException::withMessages(['barcode' => 'NOT_FOUND']);
        }

        $transaction = $this->getOrCreateActiveTransaction($pos);
        $detail = $transaction->details()->where('item_id', $item->id)->first();

        if (! $detail) {
            throw ValidationException::withMessages(['barcode' => 'NOT_FOUND']);
        }

        $currentQty = (int) $detail->qty;
        $diffQty = $qty - $currentQty;
        $pool = $this->currentPool($pos);

        if ($diffQty > 0) {
            $neededTickets = $item->ticket_redeem_qty * $diffQty;
            if ($pool < $neededTickets || $item->stock < $diffQty) {
                throw ValidationException::withMessages(['ticket' => 'INSUFFICIENT_TICKET']);
            }
        }

        return DB::transaction(function () use ($pos, $item, $transaction, $detail, $qty, $diffQty) {
            $pool = $this->currentPool($pos);

            if ($diffQty > 0) {
                $stockBefore = $item->stock;
                $this->itemRepository->decrementStock($item, $diffQty, [
                    'type' => 'redeem',
                    'reference_type' => RedeemTransaction::class,
                    'reference_id' => $transaction->id,
                    'notes' => "Penyesuaian qty redeem transaksi {$transaction->transaction_code} (Pos {$pos})",
                ]);
                $item->refresh();

                $detail->update([
                    'qty' => $qty,
                    'ticket_used' => $item->ticket_redeem_qty * $qty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $item->stock,
                ]);

                $pool -= $item->ticket_redeem_qty * $diffQty;
                Session::put($this->key($pos, 'pool'), $pool);
            } elseif ($diffQty < 0) {
                $restoreQty = abs($diffQty);
                $stockBefore = $item->stock;
                $this->itemRepository->incrementStock($item, $restoreQty, [
                    'type' => 'in',
                    'reference_type' => RedeemTransaction::class,
                    'reference_id' => $transaction->id,
                    'notes' => "Kembalikan stock akibat pengurangan qty redeem transaksi {$transaction->transaction_code} (Pos {$pos})",
                ]);
                $item->refresh();

                $detail->update([
                    'qty' => $qty,
                    'ticket_used' => $item->ticket_redeem_qty * $qty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $item->stock,
                ]);

                $pool += $item->ticket_redeem_qty * $restoreQty;
                Session::put($this->key($pos, 'pool'), $pool);
            } else {
                $detail->update([
                    'qty' => $qty,
                    'ticket_used' => $item->ticket_redeem_qty * $qty,
                ]);
            }

            $this->syncTransactionTotals($transaction);

            $totalUsed = (int) $transaction->fresh()->total_ticket_used;
            Session::put($this->key($pos, 'total_used'), $totalUsed);

            return [
                'item' => $item,
                'detail' => $detail->fresh(),
                'pool' => $pool,
                'total_used' => $totalUsed,
                'transaction' => $transaction->fresh(),
            ];
        });
    }

    public function removeItemFromCart(int $pos, string $barcode): array
    {
        $item = $this->itemRepository->findByBarcode($barcode);

        if (! $item) {
            throw ValidationException::withMessages(['barcode' => 'NOT_FOUND']);
        }

        $transaction = $this->getOrCreateActiveTransaction($pos);
        $detail = $transaction->details()->where('item_id', $item->id)->first();

        if (! $detail) {
            throw ValidationException::withMessages(['barcode' => 'NOT_FOUND']);
        }

        return DB::transaction(function () use ($pos, $item, $transaction, $detail) {
            $qty = (int) $detail->qty;
            $ticketUsed = (int) $detail->ticket_used;

            $this->itemRepository->incrementStock($item, $qty, [
                'type' => 'in',
                'reference_type' => RedeemTransaction::class,
                'reference_id' => $transaction->id,
                'notes' => "Hapus item {$item->name} dari keranjang redeem transaksi {$transaction->transaction_code} (Pos {$pos})",
            ]);

            $pool = $this->currentPool($pos) + $ticketUsed;
            Session::put($this->key($pos, 'pool'), $pool);

            $totalUsed = max(0, (int) Session::get($this->key($pos, 'total_used'), 0) - $ticketUsed);
            Session::put($this->key($pos, 'total_used'), $totalUsed);

            $detail->delete();
            $this->syncTransactionTotals($transaction);

            return [
                'item' => $item,
                'pool' => $pool,
                'total_used' => $totalUsed,
                'transaction' => $transaction->fresh(),
            ];
        });
    }

    /**
     * Daftar tiket yang sudah discan di Pos ini dan belum terpakai (belum ikut
     * transaksi redeem manapun) — dipakai untuk menampilkan riwayat scan yang
     * bisa dihapus satu per satu kalau scanner salah baca.
     */
    public function listScannedTickets(int $pos)
    {
        $ids = Session::get($this->key($pos, 'scanned_ticket_ids'), []);

        if (empty($ids)) {
            return collect();
        }

        return RedeemTicketScan::whereIn('id', $ids)
            ->where('is_used', false)
            ->orderByDesc('scanned_at')
            ->get();
    }

    /**
     * Hapus satu tiket yang salah scan (belum terpakai). Nilainya otomatis
     * dikurangi dari pool & total tiket discan Pos ini.
     */
    public function deleteScannedTicket(int $pos, int $scanId): array
    {
        $ids = Session::get($this->key($pos, 'scanned_ticket_ids'), []);

        if (! in_array($scanId, $ids, true)) {
            throw ValidationException::withMessages([
                'ticket' => 'Tiket tidak ditemukan pada sesi Pos ini.',
            ]);
        }

        $scan = RedeemTicketScan::where('id', $scanId)->where('is_used', false)->first();

        if (! $scan) {
            throw ValidationException::withMessages([
                'ticket' => 'Tiket sudah terpakai dalam transaksi redeem dan tidak bisa dihapus.',
            ]);
        }

        $value = (int) $scan->ticket_code_5digit;
        $scan->delete();

        Session::put($this->key($pos, 'scanned_ticket_ids'), array_values(array_diff($ids, [$scanId])));

        $pool = max(0, $this->currentPool($pos) - $value);
        Session::put($this->key($pos, 'pool'), $pool);

        $totalScannedValue = max(0, (int) Session::get($this->key($pos, 'total_scanned_value'), 0) - $value);
        Session::put($this->key($pos, 'total_scanned_value'), $totalScannedValue);

        return [
            'pool' => $pool,
            'total_scanned_value' => $totalScannedValue,
        ];
    }

    /**
     * Reset seluruh sesi redeem pada satu Pos.
     * Semua tiket dan transaksi aktif dihapus, serta stok hadiah dikembalikan.
     */
    public function resetScannedTickets(int $pos): array
    {
        $ids = Session::get($this->key($pos, 'scanned_ticket_ids'), []);
        $transactionId = Session::get($this->key($pos, 'transaction_id'));

        return DB::transaction(function () use ($pos, $ids, $transactionId) {
            $transaction = $transactionId
                ? RedeemTransaction::with(['details.item'])->find($transactionId)
                : null;

            if ($transaction) {
                foreach ($transaction->details as $detail) {
                    if (! $detail->item) {
                        continue;
                    }

                    $this->itemRepository->incrementStock($detail->item, (int) $detail->qty, [
                        'type' => 'in',
                        'reference_type' => RedeemTransaction::class,
                        'reference_id' => $transaction->id,
                        'notes' => "Kembalikan stock akibat reset sesi redeem {$transaction->transaction_code} (Pos {$pos})",
                    ]);
                }
            }

            if (! empty($ids)) {
                RedeemTicketScan::whereIn('id', $ids)->delete();
            }

            $transaction?->delete();

            Session::forget([
                $this->key($pos, 'pool'),
                $this->key($pos, 'total_scanned_value'),
                $this->key($pos, 'total_used'),
                $this->key($pos, 'transaction_id'),
                $this->key($pos, 'scanned_ticket_ids'),
                $this->key($pos, 'member_phone'),
            ]);

            return [
                'pool' => 0,
                'total_scanned_value' => 0,
                'total_used' => 0,
            ];
        });
    }

    /**
     * Tambah tiket secara manual (staff mengetik langsung nilai tiketnya) untuk
     * kasus tiket rusak/tidak terbaca scanner sama sekali.
     */
    public function addManualTicket(int $pos, int $value): array
    {
        $this->rejectMemberTicketScan($pos);
        if ($value <= 0) {
            throw ValidationException::withMessages([
                'value' => 'Nilai tiket manual harus lebih dari 0.',
            ]);
        }

        if ($value > 99999) {
            throw ValidationException::withMessages([
                'value' => 'Nilai tiket manual maksimal 99999 agar sesuai format audit 5 digit.',
            ]);
        }

        $code = str_pad((string) $value, 5, '0', STR_PAD_LEFT);
        $manualBarcode = 'MANUAL-'.now()->format('YmdHis').'-'.$value;

        $scan = RedeemTicketScan::create([
            'redeem_transaction_id' => null,
            'ticket_barcode' => $manualBarcode,
            'ticket_code_5digit' => $code,
            'is_used' => false,
            'user_id' => Auth::id(),
            'scanned_at' => now(),
        ]);

        $ids = Session::get($this->key($pos, 'scanned_ticket_ids'), []);
        $ids[] = $scan->id;
        Session::put($this->key($pos, 'scanned_ticket_ids'), $ids);

        $pool = $this->currentPool($pos) + $value;
        Session::put($this->key($pos, 'pool'), $pool);

        $totalScannedValue = (int) Session::get($this->key($pos, 'total_scanned_value'), 0) + $value;
        Session::put($this->key($pos, 'total_scanned_value'), $totalScannedValue);

        return [
            'scan_id' => $scan->id,
            'ticket_value' => $value,
            'ticket_code' => $code,
            'pool' => $pool,
            'total_scanned_value' => $totalScannedValue,
        ];
    }

    public function finishTransaction(int $pos): ?RedeemTransaction
    {
        $transactionId = Session::get($this->key($pos, 'transaction_id'));
        $transaction = $transactionId ? RedeemTransaction::with('details')->find($transactionId) : null;

        if ($transaction) {
            $ids = Session::get($this->key($pos, 'scanned_ticket_ids'), []);
            $scans = RedeemTicketScan::whereIn('id', $ids)->get();
            $totalTicketScanned = $pos === self::MEMBER_POS
                ? (int) Session::get($this->key($pos, 'total_scanned_value'), 0)
                : $scans->sum(fn ($scan) => (int) $scan->ticket_code_5digit);

            $this->syncTransactionTotals($transaction);
            $transaction->update([
                'total_ticket_scanned' => $totalTicketScanned,
                'total_ticket_used' => $transaction->details()->sum('ticket_used'),
                'redeemed_at' => now(),
            ]);
        }

        Session::forget([
            $this->key($pos, 'pool'),
            $this->key($pos, 'total_scanned_value'),
            $this->key($pos, 'total_used'),
            $this->key($pos, 'transaction_id'),
            $this->key($pos, 'scanned_ticket_ids'),
            $this->key($pos, 'member_phone'),
        ]);

        return $transaction;
    }

    protected function getOrCreateActiveTransaction(int $pos): RedeemTransaction
    {
        $transactionId = Session::get($this->key($pos, 'transaction_id'));

        if ($transactionId) {
            $transaction = RedeemTransaction::find($transactionId);
            if ($transaction) {
                return $transaction;
            }
        }

        $transaction = RedeemTransaction::create([
            'store_id' => $this->currentStoreId(),
            'transaction_code' => $this->generateTransactionCode(),
            'redeem_type' => $pos === self::MEMBER_POS ? 'member' : 'pos',
            'member_phone' => $pos === self::MEMBER_POS ? Session::get($this->key($pos, 'member_phone')) : null,
            'user_id' => Auth::id(),
            'total_ticket_scanned' => (int) Session::get($this->key($pos, 'total_scanned_value'), 0),
            'total_ticket_used' => 0,
            'total_value' => 0,
            'redeemed_at' => now(),
        ]);

        Session::put($this->key($pos, 'transaction_id'), $transaction->id);

        return $transaction;
    }

    /**
     * Tandai tiket-tiket (FIFO, dari yang terlama di Pos ini) sebagai terpakai
     * hingga nilai kumulatifnya mencukupi $requiredValue. Murni jejak audit;
     * saldo pool sesungguhnya dikelola terpisah lewat session pool per Pos.
     */
    protected function consumeTicketsFifo(int $pos, int $requiredValue, int $transactionId): void
    {
        $ids = Session::get($this->key($pos, 'scanned_ticket_ids'), []);

        $unused = RedeemTicketScan::whereIn('id', $ids)
            ->where('is_used', false)
            ->orderBy('scanned_at')
            ->get();

        $accumulated = 0;
        foreach ($unused as $scan) {
            if ($accumulated >= $requiredValue) {
                break;
            }
            $accumulated += (int) $scan->ticket_code_5digit;
            $scan->update(['is_used' => true, 'redeem_transaction_id' => $transactionId]);
        }
    }

    protected function syncTransactionTotals(RedeemTransaction $transaction): void
    {
        $details = $transaction->details()->with('item')->get();

        $totalTicketUsed = $details->sum(fn ($detail) => (int) $detail->ticket_used);
        $totalValue = $details->sum(fn ($detail) => ((float) ($detail->item?->selling_price ?? 0)) * (int) $detail->qty);

        $transaction->update([
            'total_ticket_used' => $totalTicketUsed,
            'total_value' => $totalValue,
        ]);
    }

    protected function generateTransactionCode(): string
    {
        $prefix = 'RD-'.now()->format('Ymd').'-';
        $count = RedeemTransaction::where('transaction_code', 'like', $prefix.'%')->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Bangun session key unik per Pos, supaya state Pos 1/2/3 tidak saling
     * tercampur dan tetap tersimpan saat staff pindah-pindah tab.
     */
    protected function key(int $pos, string $suffix): string
    {
        return "redeem.pos.{$pos}.{$suffix}";
    }

    public function setMemberBalance(string $phone, int $totalTickets): array
    {
        $phone = preg_replace('/[^0-9+]/', '', trim($phone));

        if (! preg_match('/^(?:\+62|62|0)8[0-9]{7,12}$/', $phone)) {
            throw ValidationException::withMessages(['phone' => 'Nomor handphone member tidak valid.']);
        }

        if ($totalTickets < 1) {
            throw ValidationException::withMessages(['total_tickets' => 'Total tiket harus lebih dari 0.']);
        }

        $used = (int) Session::get($this->key(self::MEMBER_POS, 'total_used'), 0);
        if ($totalTickets < $used) {
            throw ValidationException::withMessages(['total_tickets' => 'Total tiket tidak boleh lebih kecil dari tiket yang sudah terpakai.']);
        }

        Session::put($this->key(self::MEMBER_POS, 'member_phone'), $phone);
        Session::put($this->key(self::MEMBER_POS, 'total_scanned_value'), $totalTickets);
        Session::put($this->key(self::MEMBER_POS, 'pool'), $totalTickets - $used);

        $transactionId = Session::get($this->key(self::MEMBER_POS, 'transaction_id'));
        if ($transactionId) {
            RedeemTransaction::whereKey($transactionId)->update([
                'redeem_type' => 'member',
                'member_phone' => $phone,
                'total_ticket_scanned' => $totalTickets,
            ]);
        }

        return ['member_phone' => $phone, 'total_scanned_value' => $totalTickets, 'total_used' => $used, 'pool' => $totalTickets - $used];
    }

    protected function rejectMemberTicketScan(int $pos): void
    {
        if ($pos === self::MEMBER_POS) {
            throw ValidationException::withMessages(['ticket' => 'Redeem Member tidak menggunakan scan tiket.']);
        }
    }

    public function syncOfflineTransaction(array $payload): RedeemTransaction
    {
        $existing = RedeemTransaction::where('offline_reference', $payload['reference'])->first();
        if ($existing) {
            return $existing;
        }

        $isMember = $payload['redeem_type'] === 'member';
        $phone = $isMember ? preg_replace('/[^0-9+]/', '', (string) ($payload['member_phone'] ?? '')) : null;
        if ($isMember && ! preg_match('/^(?:\+62|62|0)8[0-9]{7,12}$/', $phone)) {
            throw ValidationException::withMessages(['member_phone' => 'Nomor handphone member tidak valid.']);
        }

        return DB::transaction(function () use ($payload, $isMember, $phone) {
            $items = collect($payload['items'])->map(function ($line) {
                $item = $this->itemRepository->findByBarcode($line['barcode']);
                if (! $item) {
                    throw ValidationException::withMessages(['items' => "Barcode {$line['barcode']} tidak ditemukan."]);
                }
                $qty = (int) $line['qty'];
                if ($qty < 1 || $item->stock < $qty) {
                    throw ValidationException::withMessages(['items' => "Stok {$item->name} tidak mencukupi."]);
                }
                return compact('item', 'qty');
            });

            $ticketUsed = $items->sum(fn ($line) => (int) $line['item']->ticket_redeem_qty * $line['qty']);
            $totalTickets = (int) $payload['total_tickets'];
            if ($ticketUsed > $totalTickets) {
                throw ValidationException::withMessages(['total_tickets' => 'Total tiket offline tidak mencukupi.']);
            }

            $transaction = RedeemTransaction::create([
                'store_id' => $this->currentStoreId(),
                'transaction_code' => $this->generateTransactionCode(),
                'redeem_type' => $isMember ? 'member' : 'pos',
                'member_phone' => $phone,
                'offline_reference' => $payload['reference'],
                'user_id' => Auth::id(),
                'total_ticket_scanned' => $totalTickets,
                'total_ticket_used' => $ticketUsed,
                'total_value' => 0,
                'redeemed_at' => $payload['created_at'] ?? now(),
            ]);

            foreach ($items as $line) {
                $item = $line['item'];
                $qty = $line['qty'];
                $before = $item->stock;
                $this->itemRepository->decrementStock($item, $qty, [
                    'type' => 'redeem',
                    'reference_type' => RedeemTransaction::class,
                    'reference_id' => $transaction->id,
                    'notes' => "Sinkronisasi redeem offline {$transaction->transaction_code}",
                ]);
                $item->refresh();
                $transaction->details()->create([
                    'item_id' => $item->id,
                    'item_barcode' => $item->barcode,
                    'item_name' => $item->name,
                    'qty' => $qty,
                    'ticket_used' => (int) $item->ticket_redeem_qty * $qty,
                    'stock_before' => $before,
                    'stock_after' => $item->stock,
                ]);
            }

            $this->syncTransactionTotals($transaction);
            return $transaction->fresh();
        });
    }

    protected function currentStoreId(): ?int
    {
        return Auth::user()?->store_id
            ?? \App\Models\Store::where('code', 'S040')->value('id');
    }
}
