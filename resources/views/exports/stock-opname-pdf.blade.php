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
        .text-danger { color: #c00; }
        .text-success { color: #080; }
    </style>
</head>
<body>
    <h3>Laporan Stock Opname — {{ $opname->code }}</h3>
    <div class="subtitle">
        Tanggal: {{ $opname->opname_date->translatedFormat('d F Y') }} |
        Petugas: {{ $opname->user->name }} |
        Status: {{ ucfirst($opname->status) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th><th>Barcode</th><th>Nama Item</th>
                <th class="text-center">Expected</th><th class="text-center">Actual</th><th class="text-center">Selisih</th>
            </tr>
        </thead>
        <tbody>
            @forelse($opname->details as $i => $detail)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $detail->item->barcode ?? '-' }}</td>
                    <td>{{ $detail->item->name ?? '(item dihapus)' }}</td>
                    <td class="text-center">{{ $detail->expected_stock }}</td>
                    <td class="text-center">{{ $detail->actual_stock }}</td>
                    <td class="text-center {{ $detail->difference < 0 ? 'text-danger' : ($detail->difference > 0 ? 'text-success' : '') }}">
                        {{ $detail->difference > 0 ? '+' : '' }}{{ $detail->difference }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
