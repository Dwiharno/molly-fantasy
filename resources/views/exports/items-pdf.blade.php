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
        .text-end { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h3>Laporan Master Item — {{ \App\Models\Setting::get('outlet_name', config('app.name')) }}</h3>
    <div class="subtitle">Dicetak: {{ now()->translatedFormat('d F Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Barcode</th>
                <th>Nama Item</th>
                <th>Allocation</th>
                <th>Category</th>
                <th>Sub Category</th>
                <th class="text-end">Price</th>
                <th class="text-center">Nilai Tiket</th>
                <th class="text-center">Stok</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->barcode }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->allocation ?? '-' }}</td>
                    <td>{{ $item->category ?? '-' }}</td>
                    <td>{{ $item->sub_category ?? '-' }}</td>
                    <td class="text-end">Rp {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $item->ticket_redeem_qty }}</td>
                    <td class="text-center">{{ $item->stock }}</td>
                    <td class="text-center">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
