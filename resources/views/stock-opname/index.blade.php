@extends('layouts.app')

@section('title', 'Stock Opname')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Stock Opname</h4>
    @can('create', App\Models\StockOpname::class)
        <form action="{{ route('stock-opname.start') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i> Mulai Sesi Baru
            </button>
        </form>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <table id="tableOpname" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>#</th><th>Kode</th><th>Tanggal</th><th>Petugas</th>
                    <th class="text-center">Jumlah Item</th><th class="text-center">Status</th><th class="text-center">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const table = $('#tableOpname').DataTable({
        processing: true, serverSide: true, ajax: '{{ route('stock-opname.data') }}',
        order: [[0, 'desc']],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'code' },
            { data: 'opname_date' },
            { data: 'user.name', defaultContent: '-' },
            { data: 'details_count', className: 'text-center' },
            { data: 'status_badge', orderable: false, className: 'text-center' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' },
        ]
    });

    $(document).on('click', '.btn-delete-opname', function () {
        const id = $(this).data('id');
        mfConfirmDelete(id, function () {
            $.ajax({
                url: '/stock-opname/' + id, method: 'DELETE',
                success: function (res) { mfToast('success', res.message); table.ajax.reload(); },
                error: function (xhr) { mfToast('error', xhr.responseJSON?.message || 'Gagal menghapus sesi.'); }
            });
        });
    });
});
</script>
@endpush
