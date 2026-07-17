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
    <h3>Riwayat Redeem Hadiah</h3>
    <div class="subtitle">Dicetak: {{ now()->translatedFormat('d F Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th><th>No. Transaksi</th><th>Kasir</th><th>Barcode</th>
                <th>Nama Barang</th><th class="text-center">Qty</th><th class="text-center">Tiket Dipakai</th>
                <th class="text-center">Tiket Discan</th><th class="text-center">Sisa Tiket</th>
            </tr>
        </thead>
        <tbody>
            @forelse($details as $detail)
                <tr>
                    <td>{{ $detail->redeemTransaction->redeemed_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $detail->redeemTransaction->transaction_code }}</td>
                    <td>{{ $detail->redeemTransaction->user->name ?? '-' }}</td>
                    <td>{{ $detail->item_barcode }}</td>
                    <td>{{ $detail->item_name }}</td>
                    <td class="text-center">{{ $detail->qty }}</td>
                    <td class="text-center">{{ $detail->ticket_used }}</td>
                    <td class="text-center">{{ (int) $detail->redeemTransaction->total_ticket_scanned }}</td>
                    <td class="text-center">{{ max(0, (int) $detail->redeemTransaction->total_ticket_scanned - (int) $detail->redeemTransaction->total_ticket_used) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
