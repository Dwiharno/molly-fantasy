@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
<h4 class="mb-4">Laporan</h4>

<ul class="nav nav-tabs mb-3" id="laporanTabs">
    <li class="nav-item"><button class="nav-link active" data-type="redeem_pos">Redeem POS</button></li>
    <li class="nav-item"><button class="nav-link" data-type="redeem_member">Redeem Member</button></li>
    <li class="nav-item"><button class="nav-link" data-type="stock">Stock</button></li>
    <li class="nav-item"><button class="nav-link" data-type="barang_masuk">Barang Masuk</button></li>
    <li class="nav-item"><button class="nav-link" data-type="barang_keluar">Barang Keluar</button></li>
    <li class="nav-item"><button class="nav-link" data-type="user">User</button></li>
    <li class="nav-item"><button class="nav-link" data-type="selisih_stock">Selisih Stock</button></li>
</ul>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3 filter-date">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" id="filterDateFrom" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 filter-date">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" id="filterDateTo" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 filter-category d-none">
                <label class="form-label small">Kategori</label>
                <select id="filterCategory" class="form-select form-select-sm select2">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 filter-cashier d-none">
                <label class="form-label small">Kasir</label>
                <select id="filterCashier" class="form-select form-select-sm select2">
                    <option value="">Semua Kasir</option>
                    @foreach($cashiers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <a href="#" id="btnExportExcel" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-file-excel me-1"></i>Excel</a>
                <a href="#" id="btnExportPdf" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table id="tableLaporan" class="table table-hover align-middle w-100"></table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.select2').select2({ theme: 'default', width: '100%' });
    const rupiah = value => 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    const moneyColumn = (data, title) => ({ data, title, className: 'text-end', render: value => rupiah(value) });

    const columnDefs = {
        redeem_pos: [
            { data: 'DT_RowIndex', title: '#', orderable: false },
            { data: 'tanggal', title: 'Tanggal' },
            { data: 'redeem_transaction.transaction_code', title: 'No. Transaksi' },
            { data: 'kasir', title: 'Kasir' },
            { data: 'item_barcode', title: 'Barcode' },
            { data: 'item_name', title: 'Nama Barang' },
            { data: 'qty', title: 'Qty', className: 'text-center' },
            moneyColumn('unit_price', 'Harga Satuan'),
            moneyColumn('item_value', 'Total Value'),
            { data: 'ticket_used', title: 'Tiket', className: 'text-center' },
        ],
        redeem_member: [
            { data: 'DT_RowIndex', title: '#', orderable: false },
            { data: 'tanggal', title: 'Tanggal' },
            { data: 'redeem_transaction.transaction_code', title: 'No. Transaksi' },
            { data: 'kasir', title: 'Kasir' },
            { data: 'item_barcode', title: 'Barcode' },
            { data: 'item_name', title: 'Nama Barang' },
            { data: 'qty', title: 'Qty', className: 'text-center' },
            moneyColumn('unit_price', 'Harga Satuan'),
            moneyColumn('item_value', 'Total Value'),
            { data: 'ticket_used', title: 'Tiket Member', className: 'text-center' },
        ],
        stock: [
            { data: 'DT_RowIndex', title: '#', orderable: false },
            { data: 'barcode', title: 'Barcode' },
            { data: 'name', title: 'Nama Item' },
            { data: 'category', title: 'Kategori' },
            { data: 'stock', title: 'Stok', className: 'text-center' },
            moneyColumn('unit_price', 'Harga Satuan'),
            moneyColumn('item_value', 'Total Value'),
            { data: 'minimum_stock', title: 'Min. Stok', className: 'text-center' },
            { data: 'status_label', title: 'Status', className: 'text-center' },
        ],
        barang_masuk: [
            { data: 'DT_RowIndex', title: '#', orderable: false },
            { data: 'created_at', title: 'Tanggal' },
            { data: 'item_barcode', title: 'Barcode' },
            { data: 'item_name', title: 'Nama Item' },
            { data: 'quantity', title: 'Qty', className: 'text-center' },
            moneyColumn('unit_price', 'Harga Satuan'),
            moneyColumn('item_value', 'Total Value'),
            { data: 'notes', title: 'Catatan', defaultContent: '-' },
        ],
        barang_keluar: [
            { data: 'DT_RowIndex', title: '#', orderable: false },
            { data: 'created_at', title: 'Tanggal' },
            { data: 'item_barcode', title: 'Barcode' },
            { data: 'item_name', title: 'Nama Item' },
            { data: 'quantity', title: 'Qty', className: 'text-center' },
            moneyColumn('unit_price', 'Harga Satuan'),
            moneyColumn('item_value', 'Total Value'),
            { data: 'notes', title: 'Catatan', defaultContent: '-' },
        ],
        user: [
            { data: 'DT_RowIndex', title: '#', orderable: false },
            { data: 'name', title: 'Nama' },
            { data: 'email', title: 'Email' },
            { data: 'role_label', title: 'Role' },
            { data: 'status_label', title: 'Status', className: 'text-center' },
            { data: 'last_login', title: 'Login Terakhir' },
        ],
        selisih_stock: [
            { data: 'DT_RowIndex', title: '#', orderable: false },
            { data: 'opname_code', title: 'Kode Opname' },
            { data: 'opname_date', title: 'Tanggal' },
            { data: 'item_barcode', title: 'Barcode' },
            { data: 'item_name', title: 'Nama Item' },
            { data: 'expected_stock', title: 'Expected', className: 'text-center' },
            { data: 'actual_stock', title: 'Actual', className: 'text-center' },
            { data: 'difference', title: 'Selisih', className: 'text-center' },
            moneyColumn('unit_price', 'Harga Satuan'),
            moneyColumn('item_value', 'Total Value Selisih'),
        ],
    };

    let currentType = 'redeem_pos';
    let table = null;

    function toggleFilters(type) {
        $('.filter-date').toggleClass('d-none', !['redeem_pos', 'redeem_member', 'barang_masuk', 'barang_keluar', 'selisih_stock'].includes(type));
        $('.filter-category').toggleClass('d-none', type !== 'stock');
        $('.filter-cashier').toggleClass('d-none', !['redeem_pos', 'redeem_member'].includes(type));
    }

    function loadTable(type) {
        currentType = type;
        toggleFilters(type);

        if (table) { table.destroy(); $('#tableLaporan').empty(); }

        let theadHtml = '<thead><tr>';
        columnDefs[type].forEach(c => theadHtml += '<th>' + c.title + '</th>');
        theadHtml += '</tr></thead>';
        $('#tableLaporan').html(theadHtml);

        table = $('#tableLaporan').DataTable({
            processing: true, serverSide: true,
            ajax: {
                url: '{{ route('laporan.data') }}',
                data: function (d) {
                    d.type = currentType;
                    d.date_from = $('#filterDateFrom').val();
                    d.date_to = $('#filterDateTo').val();
                    d.category = $('#filterCategory').val();
                    d.user_id = $('#filterCashier').val();
                }
            },
            columns: columnDefs[type],
        });
    }

    $('#laporanTabs .nav-link').on('click', function () {
        $('#laporanTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        loadTable($(this).data('type'));
    });

    $('#filterDateFrom, #filterDateTo, #filterCategory, #filterCashier').on('change', function () {
        table.ajax.reload();
    });

    function buildExportUrl(base) {
        const params = new URLSearchParams({
            type: currentType,
            date_from: $('#filterDateFrom').val() || '',
            date_to: $('#filterDateTo').val() || '',
            category: $('#filterCategory').val() || '',
            user_id: $('#filterCashier').val() || '',
        });
        return base + '?' + params.toString();
    }

    $('#btnExportExcel').on('click', function (e) {
        e.preventDefault();
        window.location.href = buildExportUrl('{{ route('laporan.export.excel') }}');
    });
    $('#btnExportPdf').on('click', function (e) {
        e.preventDefault();
        window.location.href = buildExportUrl('{{ route('laporan.export.pdf') }}');
    });

    loadTable('redeem_pos');
});
</script>
@endpush
