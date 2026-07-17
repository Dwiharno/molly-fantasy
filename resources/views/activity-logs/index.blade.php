@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
<h4 class="mb-4">Log Aktivitas</h4>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label small">User</label>
                <select id="filterUser" class="form-select form-select-sm select2">
                    <option value="">Semua User</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Modul</label>
                <select id="filterModule" class="form-select form-select-sm select2">
                    <option value="">Semua Modul</option>
                    @foreach($modules as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Aksi</label>
                <select id="filterAction" class="form-select form-select-sm select2">
                    <option value="">Semua Aksi</option>
                    <option value="login">Login</option>
                    <option value="logout">Logout</option>
                    <option value="create">Tambah</option>
                    <option value="update">Edit</option>
                    <option value="delete">Delete</option>
                    <option value="redeem">Redeem</option>
                    <option value="complete">Stock Opname Selesai</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" id="filterDateFrom" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" id="filterDateTo" class="form-control form-control-sm">
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table id="tableLogs" class="table table-hover align-middle w-100">
            <thead>
                <tr><th>#</th><th>Waktu</th><th>User</th><th>Aksi</th><th>Modul</th><th>Deskripsi</th><th>IP</th></tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.select2').select2({ theme: 'default', width: '100%' });

    const table = $('#tableLogs').DataTable({
        processing: true, serverSide: true,
        order: [[1, 'desc']],
        ajax: {
            url: '{{ route('activity-logs.data') }}',
            data: function (d) {
                d.user_id = $('#filterUser').val();
                d.module = $('#filterModule').val();
                d.action = $('#filterAction').val();
                d.date_from = $('#filterDateFrom').val();
                d.date_to = $('#filterDateTo').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'waktu' },
            { data: 'user_name' },
            { data: 'action_badge', orderable: false },
            { data: 'module' },
            { data: 'description' },
            { data: 'ip_address', defaultContent: '-' },
        ]
    });

    $('#filterUser, #filterModule, #filterAction, #filterDateFrom, #filterDateTo').on('change', function () {
        table.ajax.reload();
    });
});
</script>
@endpush
