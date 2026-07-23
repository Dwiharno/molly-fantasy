<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h3 { margin-bottom: 2px; }
        .subtitle { color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background: #f1f1f1; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h3>{{ $title }}</h3>
    <div class="subtitle">Dicetak: {{ now()->translatedFormat('d F Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                @switch($type)
                    @case('redeem_pos')
                    @case('redeem_member')
                        <th>Tanggal</th><th>No. Transaksi</th><th>Kasir</th><th>Barcode</th><th>Nama Barang</th><th class="text-center">Qty</th><th>Harga Satuan</th><th>Total Value</th><th class="text-center">Tiket</th>
                        @break
                    @case('stock')
                        <th>Barcode</th><th>Nama Item</th><th>Kategori</th><th class="text-center">Stok</th><th>Harga Satuan</th><th>Total Value</th><th class="text-center">Min. Stok</th><th class="text-center">Status</th>
                        @break
                    @case('barang_masuk')
                    @case('barang_keluar')
                        <th>Tanggal</th><th>Barcode</th><th>Nama Item</th><th class="text-center">Qty</th><th>Harga Satuan</th><th>Total Value</th><th>Catatan</th>
                        @break
                    @case('user')
                        <th>Nama</th><th>Email</th><th>Role</th><th class="text-center">Status</th><th>Login Terakhir</th>
                        @break
                    @case('selisih_stock')
                        <th>Kode Opname</th><th>Tanggal</th><th>Barcode</th><th>Nama Item</th><th class="text-center">Expected</th><th class="text-center">Actual</th><th class="text-center">Selisih</th><th>Harga Satuan</th><th>Total Value Selisih</th>
                        @break
                @endswitch
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @switch($type)
                        @case('redeem_pos')
                        @case('redeem_member')
                            <td>{{ $row->redeemTransaction->redeemed_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $row->redeemTransaction->transaction_code }}</td>
                            <td>{{ $row->redeemTransaction->user->name ?? '-' }}</td>
                            <td>{{ $row->item_barcode }}</td>
                            <td>{{ $row->item_name }}</td>
                            <td class="text-center">{{ $row->qty }}</td>
                            <td>Rp {{ number_format($row->item?->selling_price ?? 0, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format(($row->item?->selling_price ?? 0) * $row->qty, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $row->ticket_used }}</td>
                            @break
                        @case('stock')
                            <td>{{ $row->barcode }}</td>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->category ?? '-' }}</td>
                            <td class="text-center">{{ $row->stock }}</td>
                            <td>Rp {{ number_format($row->selling_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($row->selling_price * $row->stock, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $row->minimum_stock }}</td>
                            <td class="text-center">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            @break
                        @case('barang_masuk')
                        @case('barang_keluar')
                            <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $row->item_barcode }}</td>
                            <td>{{ $row->item_name }}</td>
                            <td class="text-center">{{ $row->quantity }}</td>
                            <td>Rp {{ number_format($row->unit_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($row->unit_price * abs($row->quantity), 0, ',', '.') }}</td>
                            <td>{{ $row->notes ?? '-' }}</td>
                            @break
                        @case('user')
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->email }}</td>
                            <td>{{ \App\Models\User::ROLES[$row->role] ?? $row->role }}</td>
                            <td class="text-center">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td>{{ $row->last_login_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            @break
                        @case('selisih_stock')
                            <td>{{ $row->stockOpname->code ?? '-' }}</td>
                            <td>{{ $row->stockOpname->opname_date?->format('d/m/Y') ?? '-' }}</td>
                            <td>{{ $row->item->barcode ?? '-' }}</td>
                            <td>{{ $row->item->name ?? '(item dihapus)' }}</td>
                            <td class="text-center">{{ $row->expected_stock }}</td>
                            <td class="text-center">{{ $row->actual_stock }}</td>
                            <td class="text-center">{{ $row->difference }}</td>
                            <td>Rp {{ number_format($row->item?->selling_price ?? 0, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format(($row->item?->selling_price ?? 0) * $row->difference, 0, ',', '.') }}</td>
                            @break
                    @endswitch
                </tr>
            @empty
                <tr><td colspan="9" class="text-center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
