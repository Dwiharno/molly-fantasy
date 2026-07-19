@extends('layouts.app')

@section('title', 'Redeem Offline')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="mb-1">Redeem Offline</h4><div class="text-muted small">Transaksi disimpan di perangkat dan otomatis disinkronkan saat internet kembali.</div></div>
    <div><span id="networkBadge" class="badge"></span> <a href="{{ route('redeem.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4"><label class="form-label">Jenis Redeem</label><select id="offlineType" class="form-select"><option value="pos">POS</option><option value="member">Member</option></select></div>
                <div class="col-md-4 member-field d-none"><label class="form-label">Nomor Handphone</label><input id="offlinePhone" class="form-control" type="tel" placeholder="0812..."></div>
                <div class="col-md-4"><label class="form-label">Total Tiket</label><input id="offlineTickets" class="form-control" type="number" min="1"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2"><strong>Hadiah</strong><button id="addOfflineItem" class="btn btn-outline-primary btn-sm" type="button"><i class="fa-solid fa-plus"></i> Tambah Item</button></div>
            <div id="offlineItems"></div>
            <button id="saveOfflineTransaction" class="btn btn-primary w-100 mt-3" type="button"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Transaksi Offline</button>
        </div></div>
    </div>
    <div class="col-lg-5"><div class="card"><div class="card-header">Antrean Sinkronisasi</div><div id="offlineQueue" class="card-body"></div></div></div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const queueKey = 'molly_redeem_offline_queue_v1';
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const readQueue = () => JSON.parse(localStorage.getItem(queueKey) || '[]');
    const writeQueue = value => localStorage.setItem(queueKey, JSON.stringify(value));

    function addItem(barcode = '', qty = 1) {
        $('#offlineItems').append('<div class="row g-2 mb-2 offline-item"><div class="col-8"><input class="form-control item-barcode" placeholder="Scan / masukkan barcode hadiah" value="' + barcode + '"></div><div class="col-2"><input class="form-control item-qty" type="number" min="1" value="' + qty + '"></div><div class="col-2"><button type="button" class="btn btn-outline-danger w-100 remove-offline-item"><i class="fa-solid fa-trash"></i></button></div></div>');
    }

    function render() {
        const online = navigator.onLine;
        $('#networkBadge').attr('class', 'badge ' + (online ? 'text-bg-success' : 'text-bg-warning')).text(online ? 'Online' : 'Offline');
        const queue = readQueue();
        $('#offlineQueue').html(queue.length ? queue.map(x => '<div class="border-bottom py-2"><strong>' + x.redeem_type.toUpperCase() + '</strong> · ' + x.total_tickets.toLocaleString('id-ID') + ' tiket<br><span class="small text-muted">' + x.items.length + ' jenis hadiah · ' + new Date(x.created_at).toLocaleString('id-ID') + '</span></div>').join('') : '<div class="text-muted text-center py-3">Tidak ada antrean.</div>');
    }

    async function syncQueue() {
        if (!navigator.onLine) return render();
        const queue = readQueue();
        const remaining = [];
        for (const transaction of queue) {
            try {
                const response = await fetch('{{ route('redeem.offline-sync') }}', {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token}, body: JSON.stringify(transaction)});
                if (!response.ok) {
                    const error = await response.json().catch(() => ({}));
                    transaction.sync_error = error.message || 'Gagal sinkron';
                    remaining.push(transaction);
                }
            } catch (_) { remaining.push(transaction); }
        }
        writeQueue(remaining); render();
        if (queue.length && !remaining.length) mfToast('success', 'Semua transaksi offline berhasil disinkronkan.');
    }

    $('#offlineType').on('change', function () { $('.member-field').toggleClass('d-none', this.value !== 'member'); });
    $('#addOfflineItem').on('click', () => addItem());
    $(document).on('click', '.remove-offline-item', function () { $(this).closest('.offline-item').remove(); });
    $('#saveOfflineTransaction').on('click', function () {
        const items = $('.offline-item').map(function () { return {barcode: $(this).find('.item-barcode').val().trim(), qty: Number($(this).find('.item-qty').val())}; }).get().filter(x => x.barcode && x.qty > 0);
        const totalTickets = Number($('#offlineTickets').val());
        if (!items.length || totalTickets < 1) return mfToast('error', 'Total tiket dan minimal satu hadiah wajib diisi.');
        const payload = {reference: crypto.randomUUID(), redeem_type: $('#offlineType').val(), member_phone: $('#offlinePhone').val().trim() || null, total_tickets: totalTickets, items, created_at: new Date().toISOString()};
        const queue = readQueue(); queue.push(payload); writeQueue(queue);
        $('#offlineTickets,#offlinePhone').val(''); $('#offlineItems').empty(); addItem(); render(); syncQueue();
        mfToast('success', navigator.onLine ? 'Transaksi disimpan dan sedang disinkronkan.' : 'Transaksi aman tersimpan di perangkat.');
    });

    window.addEventListener('online', syncQueue); window.addEventListener('offline', render);
    addItem(); render(); syncQueue();
});
</script>
@endpush
