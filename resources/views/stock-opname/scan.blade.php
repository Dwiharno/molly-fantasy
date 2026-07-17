@extends('layouts.app')

@section('title', 'Scan Stock Opname - ' . $opname->code)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0">Scan Stock Opname — {{ $opname->code }}</h4>
        <span class="text-muted small">{{ $opname->opname_date->translatedFormat('d F Y') }}</span>
    </div>
    <a href="{{ route('stock-opname.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Riwayat
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card"><div><div class="stat-value" id="statScanned">{{ $opname->details->count() }}</div><div class="stat-label">Item Discan</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div><div class="stat-value" id="statExpected">{{ $opname->details->sum('expected_stock') }}</div><div class="stat-label">Total Expected</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div><div class="stat-value" id="statActual">{{ $opname->details->sum('actual_stock') }}</div><div class="stat-label">Total Actual (Scan)</div></div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <label class="form-label">Scan / Input Barcode Item</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="fa-solid fa-barcode"></i></span>
            <input type="text" id="barcodeInput" class="form-control" placeholder="Arahkan scanner ke sini atau ketik barcode lalu Enter" autofocus>
        </div>
        <div class="form-text">Mode Scan Cepat aktif — kursor otomatis kembali ke kolom ini setelah setiap scan.</div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Riwayat Scan</span>
        <div class="d-flex gap-2">
            <button type="button" id="btnUndo" class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-rotate-left me-1"></i>Undo Scan</button>
            <button type="button" id="btnReset" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-eraser me-1"></i>Reset Scan</button>
            <button type="button" id="btnComplete" class="btn btn-sm btn-primary"><i class="fa-solid fa-save me-1"></i>Simpan</button>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0" id="tableScan">
            <thead>
                <tr><th>Barcode</th><th>Nama Item</th><th class="text-center">Expected</th><th class="text-center">Actual</th><th class="text-center">Selisih Sementara</th></tr>
            </thead>
            <tbody>
                @forelse($opname->details as $detail)
                    <tr data-item-id="{{ $detail->item_id }}">
                        <td>{{ $detail->item->barcode ?? '-' }}</td>
                        <td>{{ $detail->item->name ?? '(item dihapus)' }}</td>
                        <td class="text-center exp-col">{{ $detail->expected_stock }}</td>
                        <td class="text-center act-col">{{ $detail->actual_stock }}</td>
                        <td class="text-center diff-col">{{ $detail->actual_stock - $detail->expected_stock }}</td>
                    </tr>
                @empty
                    <tr id="emptyRow"><td colspan="5" class="text-center text-muted py-4">Belum ada item yang discan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="newItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formNewItem">
                <div class="modal-header">
                    <h5 class="modal-title">Barcode Belum Terdaftar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Barcode <strong id="newItemBarcodeText"></strong> belum ada di Master Item. Tambahkan sebagai item baru:</p>
                    <input type="hidden" name="barcode" id="newItemBarcode">
                    <div class="mb-3">
                        <label class="form-label">Nama Item <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="newItemName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Awal (opsional)</label>
                        <input type="number" name="stock" id="newItemStock" class="form-control" value="0" min="0">
                    </div>
                    <div class="alert alert-warning small mb-0">
                        Harga & data lengkap lainnya bisa dilengkapi nanti di menu Master Item.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah & Lanjut Scan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const opnameId = {{ $opname->id }};
    const input = $('#barcodeInput');
    const newItemModal = new bootstrap.Modal('#newItemModal');
    let lastScannedItemId = null;

    input.trigger('focus');
    $(document).on('click', function () { input.trigger('focus'); });

    function updateStats(totals) {
        $('#statScanned').text(totals.total_scanned_items);
        $('#statExpected').text(totals.total_expected);
        $('#statActual').text(totals.total_actual);
    }

    function upsertRow(item, detail) {
        $('#emptyRow').remove();
        let row = $('#tableScan tbody tr[data-item-id="' + item.id + '"]');
        const diff = detail.actual_stock - detail.expected_stock;

        if (row.length) {
            row.find('.act-col').text(detail.actual_stock);
            row.find('.diff-col').text(diff);
        } else {
            $('#tableScan tbody').prepend(
                '<tr data-item-id="' + item.id + '">' +
                '<td>' + item.barcode + '</td><td>' + item.name + '</td>' +
                '<td class="text-center exp-col">' + detail.expected_stock + '</td>' +
                '<td class="text-center act-col">' + detail.actual_stock + '</td>' +
                '<td class="text-center diff-col">' + diff + '</td></tr>'
            );
        }
    }

    function doScan(barcode) {
        $.ajax({
            url: '/stock-opname/' + opnameId + '/scan',
            method: 'POST',
            data: { barcode: barcode },
            success: function (res) {
                lastScannedItemId = res.item.id;
                upsertRow(res.item, res.detail);
                updateStats(res.totals);
                input.val('').trigger('focus');
            },
            error: function (xhr) {
                if (xhr.status === 404) {
                    $('#newItemBarcodeText').text(barcode);
                    $('#newItemBarcode').val(barcode);
                    $('#newItemName').val('');
                    newItemModal.show();
                } else {
                    mfToast('error', xhr.responseJSON?.message || 'Gagal memproses scan.');
                    input.val('').trigger('focus');
                }
            }
        });
    }

    input.on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            const barcode = $(this).val().trim();
            if (barcode) doScan(barcode);
        }
    });

    $('#formNewItem').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route('items.quick-store') }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function () {
                newItemModal.hide();
                mfToast('success', 'Item baru ditambahkan, melanjutkan scan...');
                const barcode = $('#newItemBarcode').val();
                setTimeout(() => doScan(barcode), 300);
            },
            error: function (xhr) {
                mfToast('error', xhr.responseJSON?.message || 'Gagal menambahkan item.');
            }
        });
    });

    $('#newItemModal').on('hidden.bs.modal', function () {
        input.val('').trigger('focus');
    });

    $('#btnUndo').on('click', function () {
        if (!lastScannedItemId) {
            mfToast('info', 'Belum ada scan untuk dibatalkan.');
            return;
        }
        $.ajax({
            url: '/stock-opname/' + opnameId + '/undo',
            method: 'POST',
            data: { item_id: lastScannedItemId },
            success: function (res) {
                mfToast('success', res.message);
                updateStats(res.totals);
                location.reload();
            }
        });
    });

    $('#btnReset').on('click', function () {
        Swal.fire({
            title: 'Reset seluruh scan?',
            text: 'Semua data scan pada sesi ini akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Reset',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/stock-opname/' + opnameId + '/reset', method: 'POST',
                    success: function (res) { mfToast('success', res.message); location.reload(); }
                });
            }
        });
    });

    $('#btnComplete').on('click', function () {
        Swal.fire({
            title: 'Simpan Stock Opname?',
            text: 'Stok Master Item akan disesuaikan otomatis berdasarkan hasil scan ini.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/stock-opname/' + opnameId + '/complete', method: 'POST',
                    success: function (res) {
                        mfToast('success', res.message);
                        setTimeout(() => window.location.href = res.redirect, 800);
                    },
                    error: function (xhr) { mfToast('error', xhr.responseJSON?.message || 'Gagal menyimpan.'); }
                });
            }
        });
    });
});
</script>
@endpush
