<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Struk {{ $transaction->transaction_code }}</title>
    <style>
        @page { margin: 0; }
        body {
            width: 58mm;
            margin: 0 auto;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            padding: 6px;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        .text-end { text-align: right; }
        .item-name { padding-bottom: 0; }
        .item-calculation td { padding-top: 0; }
        @media print {
            body { width: 58mm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="center bold">{{ $transaction->store?->name ?? \App\Models\Setting::get('outlet_name', config('app.name')) }}</div>
    @if($transaction->store)<div class="center">{{ $transaction->store->code }}</div>@endif
    <div class="center">{{ \App\Models\Setting::get('outlet_address', '') }}</div>
    <hr>
    <table>
        <tr><td>No. Transaksi</td><td class="text-end">{{ $transaction->transaction_code }}</td></tr>
        <tr><td>Tanggal</td><td class="text-end">{{ $transaction->redeemed_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Kasir</td><td class="text-end">{{ $transaction->user->name }}</td></tr>
        @if($transaction->redeem_type === 'member')
            <tr><td>Jenis</td><td class="text-end">Member</td></tr>
            <tr><td>No. Handphone</td><td class="text-end">{{ $transaction->member_phone }}</td></tr>
        @endif
    </table>
    <hr>
    <table>
        @foreach($transaction->details as $detail)
            @php
                $qty = max(1, (int) $detail->qty);
                $unitTicketPrice = intdiv((int) $detail->ticket_used, $qty);
            @endphp
            <tr>
                <td colspan="2" class="item-name">{{ $detail->item_name }}</td>
            </tr>
            <tr class="item-calculation">
                <td>{{ $qty }} x {{ $unitTicketPrice }} tkt</td>
                <td class="text-end">= {{ $detail->ticket_used }} tkt</td>
            </tr>
        @endforeach
    </table>
    <hr>
    <table>
        <tr><td>Total Item</td><td class="text-end">{{ $transaction->details->count() }}</td></tr>
        <tr><td>{{ $transaction->redeem_type === 'member' ? 'Total Tiket Member' : 'Total Tiket Discan' }}</td><td class="text-end">{{ $transaction->total_ticket_scanned }}</td></tr>
        <tr><td class="bold">Total Tiket Terpakai</td><td class="text-end bold">{{ $transaction->total_ticket_used }}</td></tr>
        <tr><td>Sisa Tiket</td><td class="text-end">{{ max(0, $transaction->total_ticket_scanned - $transaction->total_ticket_used) }}</td></tr>
    </table>
    <hr>
    <div class="center">Terima kasih!</div>
    <div class="center">Selamat bermain kembali :)</div>
</body>
</html>
