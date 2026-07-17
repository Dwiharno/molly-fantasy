@extends('layouts.app')

@section('title', 'History Redeem')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0">History Redeem</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('redeem.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Redeem
        </a>
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fa-solid fa-file-export me-1"></i> Export
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" id="exportExcel">Excel</a></li>
                <li><a class="dropdown-item" href="#" id="exportPdf">PDF</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-2">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" id="filterDateFrom" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" id="filterDateTo" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Kasir</label>
                <select id="filterUser" class="form-select form-select-sm select2">
                    <option value="">Semua Kasir</option>
                    @foreach($cashiers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Nama Barang</label>
                <input type="text" id="filterName" class="form-control form-control-sm" placeholder="Cari nama barang...">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Barcode</label>
                <input type="text" id="filterBarcode" class="form-control form-control-sm" placeholder="Cari barcode...">
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table id="tableHistory" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>#</th><th>Tanggal</th><th>No. Transaksi</th><th>Kasir</th>
                    <th>Barcode</th><th>Nama Barang</th><th class="text-center">Qty</th><th class="text-center">Tiket Dipakai</th>
                    <th class="text-center">Tiket Discan</th><th class="text-center">Sisa Tiket</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.select2').select2({ theme: 'default', width: '100%' });

    const table = $('#tableHistory').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: '{{ route('redeem.history.data') }}',
            data: function (d) {
                d.date_from = $('#filterDateFrom').val();
                d.date_to = $('#filterDateTo').val();
                d.user_id = $('#filterUser').val();
                d.name = $('#filterName').val();
                d.barcode = $('#filterBarcode').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'tanggal' },
            { data: 'transaction_code' },
            { data: 'kasir' },
            { data: 'item_barcode' },
            { data: 'item_name' },
            { data: 'qty', className: 'text-center' },
            { data: 'ticket_used', className: 'text-center' },
            { data: 'total_ticket_scanned', className: 'text-center' },
            { data: 'remaining_ticket', className: 'text-center' },
        ]
    });

    let filterTimeout;
    $('#filterDateFrom, #filterDateTo, #filterUser, #filterName, #filterBarcode').on('change keyup', function () {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => table.ajax.reload(), 400);
    });

    function buildExportUrl(base) {
        const params = new URLSearchParams({
            date_from: $('#filterDateFrom').val() || '',
            date_to: $('#filterDateTo').val() || '',
            user_id: $('#filterUser').val() || '',
            name: $('#filterName').val() || '',
            barcode: $('#filterBarcode').val() || '',
        });
        return base + '?' + params.toString();
    }

    $('#exportExcel').on('click', function (e) {
        e.preventDefault();
        window.location.href = buildExportUrl('{{ route('redeem.history.export.excel') }}');
    });
    $('#exportPdf').on('click', function (e) {
        e.preventDefault();
        window.location.href = buildExportUrl('{{ route('redeem.history.export.pdf') }}');
    });
});
</script>
@endpush
