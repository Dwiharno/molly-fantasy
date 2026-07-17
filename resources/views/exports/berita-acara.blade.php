<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h3 { text-align: center; margin-bottom: 0; text-transform: uppercase; }
        .center { text-align: center; }
        .meta { margin: 16px 0; }
        .meta td { padding: 2px 6px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th, table.data td { border: 1px solid #333; padding: 5px 6px; }
        table.data th { background: #eee; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .signature { margin-top: 60px; width: 100%; }
        .signature td { text-align: center; padding-top: 60px; width: 33%; }
    </style>
</head>
<body>
    <h3>Berita Acara Stock Opname</h3>
    <p class="center">{{ \App\Models\Setting::get('outlet_name', config('app.name')) }}</p>

    <table class="meta">
        <tr><td>No. Opname</td><td>: {{ $opname->code }}</td></tr>
        <tr><td>Tanggal</td><td>: {{ $opname->opname_date->translatedFormat('d F Y') }}</td></tr>
        <tr><td>Petugas</td><td>: {{ $opname->user->name }}</td></tr>
        <tr><td>Jumlah Item Dihitung</td><td>: {{ $opname->details->count() }}</td></tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>No</th><th>Barcode</th><th>Nama Item</th>
                <th>Expected</th><th>Actual</th><th>Selisih</th>
            </tr>
        </thead>
        <tbody>
            @foreach($opname->details as $i => $detail)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $detail->item->barcode ?? '-' }}</td>
                    <td>{{ $detail->item->name ?? '(item dihapus)' }}</td>
                    <td class="text-center">{{ $detail->expected_stock }}</td>
                    <td class="text-center">{{ $detail->actual_stock }}</td>
                    <td class="text-center">{{ $detail->difference > 0 ? '+' : '' }}{{ $detail->difference }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="signature">
        <tr>
            <td>Dihitung oleh,<br><br><br>( {{ $opname->user->name }} )</td>
            <td>Diperiksa oleh,<br><br><br>( ______________________ )</td>
            <td>Disetujui oleh,<br><br><br>( ______________________ )</td>
        </tr>
    </table>
</body>
</html>
