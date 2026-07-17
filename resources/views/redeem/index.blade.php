@extends('layouts.app')

@section('title', 'Redeem Hadiah')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Redeem Hadiah</h4>
    <a href="{{ route('redeem.history') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-clock-rotate-left me-1"></i> Riwayat Redeem
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-9">
        <ul class="nav nav-tabs mb-3" id="posTabs">
            @foreach($posList as $pos)
                <li class="nav-item">
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-pos="{{ $pos }}">
                        <i class="fa-solid fa-cash-register me-1"></i> Pos {{ $pos }}
                    </button>
                </li>
            @endforeach
        </ul>

        @foreach($posList as $pos)
            @php($state = $posStates[$pos])
            <div class="pos-panel" data-pos-panel="{{ $pos }}" style="{{ $loop->first ? '' : 'display:none' }}">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-ticket"></i></div>
                            <div>
                                <div class="stat-value stat-total-scanned">{{ number_format($state['total_scanned_value']) }}</div>
                                <div class="stat-label">Total Tiket Discan</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning-subtle text-warning"><i class="fa-solid fa-arrow-right-arrow-left"></i></div>
                            <div>
                                <div class="stat-value stat-total-used">{{ number_format($state['total_used']) }}</div>
                                <div class="stat-label">Tiket Terpakai</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-coins"></i></div>
                            <div>
                                <div class="stat-value stat-pool">{{ number_format($state['pool']) }}</div>
                                <div class="stat-label">Sisa Tiket</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>1. Scan Tiket</span>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-reset-tickets">
                                    <i class="fa-solid fa-eraser me-1"></i> Reset
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="input-group input-group-lg mb-2">
                                    <span class="input-group-text"><i class="fa-solid fa-ticket"></i></span>
                                    <input type="text" class="form-control ticket-input" placeholder="Scan / paste barcode tiket lalu Enter...">
                                </div>
                                <div class="form-text mb-3">Barcode 16 digit. Nol di depan yang hilang (akibat paste dari Excel/Sheets) otomatis dikembalikan.</div>

                                <div class="d-flex gap-2 mb-3">
                                    <input type="number" class="form-control form-control-sm manual-ticket-input" placeholder="Input nilai tiket manual..." min="1">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-add-manual-ticket text-nowrap">
                                        <i class="fa-solid fa-plus me-1"></i> Tambah Manual
                                    </button>
                                </div>
                                <div class="form-text mb-2">Untuk tiket yang rusak/tidak bisa discan — ketik langsung nilai tiketnya.</div>

                                <p class="small fw-semibold mb-1">Riwayat Scan (klik <i class="fa-solid fa-trash text-danger"></i> untuk hapus jika salah baca)</p>
                                <div class="ticket-list" style="max-height: 220px; overflow-y: auto;">
                                    @forelse($state['scanned_tickets'] as $ticket)
                                        <div class="d-flex justify-content-between align-items-center border-bottom py-1 small ticket-row" data-scan-id="{{ $ticket->id }}">
                                            <div>
                                                <span class="fw-semibold">{{ $ticket->ticket_code_5digit }}</span>
                                                <span class="text-muted">({{ (int) $ticket->ticket_code_5digit }} tiket)</span>
                                                @if(str_starts_with($ticket->ticket_barcode, 'MANUAL-'))
                                                    <span class="badge text-bg-secondary">Manual</span>
                                                @endif
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-ticket" data-scan-id="{{ $ticket->id }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    @empty
                                        <div class="text-muted small text-center py-2 ticket-list-empty">Belum ada tiket discan.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">2. Scan Hadiah</div>
                            <div class="card-body">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="fa-solid fa-gift"></i></span>
                                    <input type="text" class="form-control item-input" placeholder="Scan barcode hadiah...">
                                </div>
                                <div class="form-text">Jika tiket mencukupi &amp; stok tersedia, redeem diproses otomatis.</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Keranjang Redeem — Pos {{ $pos }}</span>
                                <button type="button" class="btn btn-sm btn-primary btn-finish">
                                    <i class="fa-solid fa-print me-1"></i> Selesai &amp; Cetak Struk
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <table class="table mb-0">
                                    <thead><tr><th>Hadiah</th><th class="text-center">Qty</th><th class="text-center">Tiket</th><th class="text-center">Aksi</th></tr></thead>
                                    <tbody class="cart-body">
                                        @forelse($state['cart'] as $detail)
                                            <tr data-barcode="{{ $detail->item_barcode }}">
                                                <td>{{ $detail->item_name }}</td>
                                                <td class="text-center">
                                                    <input type="number" min="1" value="{{ $detail->qty }}" class="form-control form-control-sm qty-input text-center" data-barcode="{{ $detail->item_barcode }}" style="width: 80px; margin: 0 auto;">
                                                </td>
                                                <td class="text-center">{{ $detail->ticket_used }}</td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-1 justify-content-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-update-qty" data-barcode="{{ $detail->item_barcode }}">Update</button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-barcode="{{ $detail->item_barcode }}">Hapus</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="cart-empty-row"><td colspan="4" class="text-center text-muted py-4">Belum ada hadiah diredeem.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">Cari &amp; Copy Barcode</div>
            <div class="card-body">
                <input type="text" id="itemSearchInput" class="form-control form-control-sm mb-2" placeholder="Ketik nama atau barcode...">
                <div id="itemSearchResults" style="max-height: 500px; overflow-y: auto;">
                    <div class="text-muted small text-center py-3">Ketik untuk mencari item.</div>
                </div>
                <div class="form-text">Gunakan ini kalau scanner tidak bisa membaca barcode fisik pada barang — klik untuk copy, lalu paste ke kolom "Scan Hadiah".</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    // ------- Tab switching (state tiap Pos independen & tetap tersimpan) -------
    $('#posTabs .nav-link').on('click', function () {
        const pos = $(this).data('pos');
        $('#posTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        $('.pos-panel').hide();
        $('.pos-panel[data-pos-panel="' + pos + '"]').show();
    });

    function panelFor(el) {
        return $(el).closest('.pos-panel');
    }

    function updateStats(panel, res) {
        if (res.total_scanned_value !== undefined) panel.find('.stat-total-scanned').text(Number(res.total_scanned_value).toLocaleString('id-ID'));
        if (res.total_used !== undefined) panel.find('.stat-total-used').text(Number(res.total_used).toLocaleString('id-ID'));
        if (res.pool !== undefined) panel.find('.stat-pool').text(Number(res.pool).toLocaleString('id-ID'));
    }

    function ticketRowHtml(ticket) {
        const manualBadge = ticket.is_manual ? '<span class="badge text-bg-secondary">Manual</span>' : '';

        return '<div class="d-flex justify-content-between align-items-center border-bottom py-1 small ticket-row" data-scan-id="' + ticket.id + '">' +
            '<div><span class="fw-semibold">' + ticket.ticket_code + '</span>' +
            '<span class="text-muted"> (' + ticket.value + ' tiket)</span> ' + manualBadge + '</div>' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-ticket" data-scan-id="' + ticket.id + '">' +
            '<i class="fa-solid fa-trash"></i></button></div>';
    }

    // ------- Scan Tiket -------
    $(document).on('keypress', '.ticket-input', function (e) {
        if (e.which !== 13) return;
        e.preventDefault();
        const input = $(this);
        const panel = panelFor(this);
        const pos = panel.data('pos-panel');
        const barcode = input.val().trim();
        if (!barcode) return;

        $.ajax({
            url: '{{ route('redeem.scan-ticket') }}',
            method: 'POST',
            data: { barcode: barcode, pos: pos },
            success: function (res) {
                updateStats(panel, res);
                panel.find('.ticket-list-empty').remove();
                if (res.ticket) panel.find('.ticket-list').prepend(ticketRowHtml(res.ticket));
                mfToast('success', 'Tiket +' + res.ticket_value + ' ditambahkan (kode: ' + res.ticket_code + ').');
                input.val('').trigger('focus');
            },
            error: function (xhr) {
                mfToast('error', xhr.responseJSON?.message || 'Gagal memproses tiket.');
                input.val('').trigger('focus');
            }
        });
    });

    // ------- Tambah Tiket Manual -------
    $(document).on('click', '.btn-add-manual-ticket', function () {
        const panel = panelFor(this);
        const pos = panel.data('pos-panel');
        const input = panel.find('.manual-ticket-input');
        const value = parseInt(input.val());

        if (!value || value <= 0) {
            mfToast('error', 'Masukkan nilai tiket yang valid.');
            return;
        }

        $.ajax({
            url: '{{ route('redeem.manual-ticket') }}',
            method: 'POST',
            data: { value: value, pos: pos },
            success: function (res) {
                updateStats(panel, res);
                panel.find('.ticket-list-empty').remove();
                if (res.ticket) panel.find('.ticket-list').prepend(ticketRowHtml(res.ticket));
                mfToast('success', 'Tiket manual +' + value + ' ditambahkan.');
                input.val('');
            },
            error: function (xhr) {
                mfToast('error', xhr.responseJSON?.message || 'Gagal menambah tiket manual.');
            }
        });
    });

    // ------- Hapus 1 Tiket (salah scan) -------
    $(document).on('click', '.btn-delete-ticket', function () {
        const panel = panelFor(this);
        const pos = panel.data('pos-panel');
        const scanId = $(this).data('scan-id');
        const row = $(this).closest('.ticket-row');

        $.ajax({
            url: '/redeem/ticket/' + scanId,
            method: 'DELETE',
            data: { pos: pos },
            success: function (res) {
                updateStats(panel, res);
                row.remove();
                if (!panel.find('.ticket-row').length) {
                    panel.find('.ticket-list').append('<div class="text-muted small text-center py-2 ticket-list-empty">Belum ada tiket discan.</div>');
                }
                mfToast('success', res.message);
            },
            error: function (xhr) {
                mfToast('error', xhr.responseJSON?.message || 'Gagal menghapus tiket.');
            }
        });
    });

    // ------- Reset Seluruh Sesi Redeem -------
    $(document).on('click', '.btn-reset-tickets', function () {
        const panel = panelFor(this);
        const pos = panel.data('pos-panel');

        Swal.fire({
            title: 'Reset seluruh sesi redeem?',
            text: 'Semua tiket, total scan, dan keranjang pada Pos ini akan dihapus. Stok hadiah akan dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Reset',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545',
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '{{ route('redeem.reset-tickets') }}',
                method: 'POST',
                data: { pos: pos },
                success: function (res) {
                    updateStats(panel, res);
                    panel.find('.ticket-list').empty().append('<div class="text-muted small text-center py-2 ticket-list-empty">Belum ada tiket discan.</div>');
                    panel.find('.cart-body').empty().append('<tr class="cart-empty-row"><td colspan="4" class="text-center text-muted py-3">Belum ada hadiah diredeem.</td></tr>');
                    mfToast('success', res.message);
                }
            });
        });
    });

    // ------- Scan Hadiah -------
    $(document).on('keypress', '.item-input', function (e) {
        if (e.which !== 13) return;
        e.preventDefault();
        const input = $(this);
        const panel = panelFor(this);
        const pos = panel.data('pos-panel');
        const barcode = input.val().trim();
        if (!barcode) return;

        $.ajax({
            url: '{{ route('redeem.scan-item') }}',
            method: 'POST',
            data: { barcode: barcode, pos: pos },
            success: function (res) {
                updateStats(panel, res);
                panel.find('.cart-empty-row').remove();
                const existingRow = panel.find('.cart-body tr[data-barcode="' + res.item.barcode + '"]');
                if (existingRow.length) {
                    existingRow.find('.qty-input').val(res.detail.qty);
                    existingRow.find('td:nth-child(3)').text(res.detail.ticket_used);
                } else {
                    panel.find('.cart-body').prepend(
                        '<tr data-barcode="' + res.item.barcode + '"><td>' + res.item.name + '</td><td class="text-center"><input type="number" min="1" value="' + res.detail.qty + '" class="form-control form-control-sm qty-input text-center" data-barcode="' + res.item.barcode + '" style="width: 80px; margin: 0 auto;"></td><td class="text-center">' + res.detail.ticket_used + '</td><td class="text-center"><div class="d-flex gap-1 justify-content-center"><button type="button" class="btn btn-sm btn-outline-primary btn-update-qty" data-barcode="' + res.item.barcode + '">Update</button><button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-barcode="' + res.item.barcode + '">Hapus</button></div></td></tr>'
                    );
                }
                mfToast('success', res.message);
                input.val('').trigger('focus');
            },
            error: function (xhr) {
                const code = xhr.responseJSON?.code;
                let title = 'Gagal';
                if (code === 'INSUFFICIENT_TICKET') title = 'Tiket Tidak Mencukupi';
                if (code === 'OUT_OF_STOCK') title = 'Stock Habis';
                if (code === 'NOT_FOUND') title = 'Barcode Tidak Ditemukan';

                Swal.fire({ icon: 'warning', title: title, text: xhr.responseJSON?.message || '' });
                input.val('').trigger('focus');
            }
        });
    });

    // ------- Update Qty Item pada Keranjang -------
    $(document).on('keydown', '.qty-input', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        $(this).closest('tr').find('.btn-update-qty').trigger('click');
    });

    $(document).on('click', '.btn-update-qty', function () {
        const panel = panelFor(this);
        const pos = panel.data('pos-panel');
        const barcode = $(this).data('barcode');
        const qtyInput = panel.find('.qty-input[data-barcode="' + barcode + '"]');
        const qty = parseInt(qtyInput.val());

        if (!qty || qty <= 0) {
            mfToast('error', 'Qty harus lebih dari 0.');
            return;
        }

        $.ajax({
            url: '{{ route('redeem.update-qty') }}',
            method: 'POST',
            data: { barcode: barcode, qty: qty, pos: pos },
            success: function (res) {
                updateStats(panel, res);
                const row = panel.find('.cart-body tr[data-barcode="' + barcode + '"]');
                if (row.length) {
                    row.find('.qty-input').val(res.detail.qty);
                    row.find('td:nth-child(3)').text(res.detail.ticket_used);
                }
                mfToast('success', res.message);
            },
            error: function (xhr) {
                const code = xhr.responseJSON?.code;
                let title = 'Gagal';
                if (code === 'INSUFFICIENT_TICKET') title = 'Tiket Tidak Mencukupi';
                if (code === 'OUT_OF_STOCK') title = 'Stock Habis';
                if (code === 'NOT_FOUND') title = 'Barcode Tidak Ditemukan';

                Swal.fire({ icon: 'warning', title: title, text: xhr.responseJSON?.message || '' });
            }
        });
    });

    // ------- Hapus Item dari Keranjang -------
    $(document).on('click', '.btn-remove-item', function () {
        const panel = panelFor(this);
        const pos = panel.data('pos-panel');
        const barcode = $(this).data('barcode');
        const row = panel.find('.cart-body tr[data-barcode="' + barcode + '"]');

        Swal.fire({
            title: 'Hapus item dari keranjang?',
            text: 'Stock dan tiket yang terkait akan dikembalikan ke saldo Pos ini.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '{{ route('redeem.remove-item') }}',
                method: 'POST',
                data: { barcode: barcode, pos: pos },
                success: function (res) {
                    updateStats(panel, res);
                    row.remove();
                    if (!panel.find('.cart-body tr[data-barcode]').length) {
                        panel.find('.cart-body').append('<tr class="cart-empty-row"><td colspan="4" class="text-center text-muted py-4">Belum ada hadiah diredeem.</td></tr>');
                    }
                    mfToast('success', res.message);
                },
                error: function (xhr) {
                    mfToast('error', xhr.responseJSON?.message || 'Gagal menghapus item dari keranjang.');
                }
            });
        });
    });

    // ------- Selesai & Cetak Struk -------
    $(document).on('click', '.btn-finish', function () {
        const panel = panelFor(this);
        const pos = panel.data('pos-panel');

        $.ajax({
            url: '{{ route('redeem.finish') }}',
            method: 'POST',
            data: { pos: pos },
            success: function (res) {
                mfToast('success', res.message);
                window.open(res.print_url, '_blank');
                setTimeout(() => location.reload(), 1000);
            },
            error: function (xhr) {
                mfToast('error', xhr.responseJSON?.message || 'Belum ada transaksi untuk diselesaikan.');
            }
        });
    });

    // ------- Panel Cari & Copy Barcode Manual -------
    let searchTimeout;
    $('#itemSearchInput').on('keyup', function () {
        clearTimeout(searchTimeout);
        const term = $(this).val().trim();

        searchTimeout = setTimeout(function () {
            $.get('{{ route('redeem.search-items') }}', { q: term }, function (res) {
                const container = $('#itemSearchResults');
                container.empty();

                if (!res.items.length) {
                    container.append('<div class="text-muted small text-center py-3">Tidak ada item ditemukan.</div>');
                    return;
                }

                res.items.forEach(function (item) {
                    container.append(
                        '<div class="d-flex justify-content-between align-items-center border-bottom py-2 small">' +
                        '<div><div class="fw-semibold">' + item.name + '</div>' +
                        '<div class="text-muted">' + item.barcode + ' • stok ' + item.stock + '</div></div>' +
                        '<button type="button" class="btn btn-sm btn-outline-secondary btn-copy-barcode" data-barcode="' + item.barcode + '">' +
                        '<i class="fa-solid fa-copy"></i></button></div>'
                    );
                });
            });
        }, 350);
    });

    $(document).on('click', '.btn-copy-barcode', function () {
        const barcode = $(this).data('barcode').toString();
        navigator.clipboard.writeText(barcode).then(function () {
            mfToast('success', 'Barcode ' + barcode + ' disalin ke clipboard.');
        });
    });
});
</script>
@endpush
