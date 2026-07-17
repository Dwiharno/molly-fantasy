@extends('layouts.app')

@section('title', 'Master Item')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0">Master Item</h4>
    <div class="d-flex gap-2 flex-wrap">
        @can('import', App\Models\Item::class)
            <a href="{{ route('items.import.form') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-file-import me-1"></i> Import Excel
            </a>
        @endcan
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fa-solid fa-file-export me-1"></i> Export
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('items.export.excel') }}">Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" href="{{ route('items.export.csv') }}">CSV</a></li>
                <li><a class="dropdown-item" href="{{ route('items.export.pdf') }}">PDF</a></li>
            </ul>
        </div>
        @can('create', App\Models\Item::class)
            <a href="{{ route('items.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i> Tambah Item
            </a>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <select id="filterCategory" class="form-select form-select-sm">
                    <option value="">Semua Category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select id="filterAllocation" class="form-select form-select-sm">
                    <option value="">Semua Allocation</option>
                    @foreach($allocations as $alloc)
                        <option value="{{ $alloc }}">{{ $alloc }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </div>
            <div class="col-md-4">
                <div class="form-check mt-1">
                    <input type="checkbox" id="filterLowStock" class="form-check-input">
                    <label class="form-check-label small" for="filterLowStock">Tampilkan stok minimum saja</label>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tableItems" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Barcode</th>
                        <th>Nama Item</th>
                        <th>Allocation</th>
                        <th>Category</th>
                        <th>Sub Category</th>
                        <th class="text-end">Price</th>
                        <th class="text-center">Nilai Tiket</th>
                        <th class="text-center">Stok</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableItemsBody"></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const body = $('#tableItemsBody');

    function loadItems() {
        $.get('{{ route('items.data') }}', {
            category: $('#filterCategory').val(),
            allocation: $('#filterAllocation').val(),
            status: $('#filterStatus').val(),
            low_stock: $('#filterLowStock').is(':checked') ? 1 : ''
        }, function (res) {
            body.empty();

            if (!res.data.length) {
                body.append('<tr><td colspan="11" class="text-center text-muted py-4">Belum ada item yang sesuai filter.</td></tr>');
                return;
            }

            $.each(res.data, function (index, item) {
                body.append(
                    '<tr>' +
                    '<td>' + item.DT_RowIndex + '</td>' +
                    '<td>' + (item.barcode || '-') + '</td>' +
                    '<td>' + (item.name || '-') + '</td>' +
                    '<td>' + (item.allocation || '-') + '</td>' +
                    '<td>' + (item.category || '-') + '</td>' +
                    '<td>' + (item.sub_category || '-') + '</td>' +
                    '<td class="text-end">Rp ' + Number(item.selling_price || 0).toLocaleString('id-ID') + '</td>' +
                    '<td class="text-center">' + (item.ticket_redeem_qty || 0) + '</td>' +
                    '<td class="text-center">' + (item.stock_badge || '-') + '</td>' +
                    '<td class="text-center">' + (item.status_badge || '-') + '</td>' +
                    '<td class="text-center">' + (item.actions || '-') + '</td>' +
                    '</tr>'
                );
            });
        });
    }

    $('#filterCategory, #filterAllocation, #filterStatus, #filterLowStock').on('change', loadItems);

    $(document).on('click', '.btn-delete-item', function () {
        const id = $(this).data('id');
        mfConfirmDelete(id, function () {
            $.ajax({
                url: '/items/' + id,
                method: 'DELETE',
                success: function (res) {
                    mfToast('success', res.message);
                    loadItems();
                },
                error: function () {
                    mfToast('error', 'Gagal menghapus item.');
                }
            });
        });
    });

    loadItems();

    @if(session('success'))
        mfToast('success', @json(session('success')));
    @endif
});
</script>
@endpush
