@extends('layouts.app')

@section('title', 'Master Outlet')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Master Outlet / Store</h4>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#storeModal" id="btnAddStore">
        <i class="fa-solid fa-plus me-1"></i> Tambah Outlet
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table id="tableStores" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>#</th><th>Kode</th><th>Nama Outlet</th><th>Account Name</th>
                    <th class="text-center">Jumlah Item</th><th class="text-center">Jumlah User</th>
                    <th class="text-center">Status</th><th class="text-center">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="storeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formStore">
                <div class="modal-header">
                    <h5 class="modal-title" id="storeModalTitle">Tambah Outlet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="storeId">
                    <div class="mb-3">
                        <label class="form-label">Kode Outlet <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="storeCode" class="form-control" placeholder="Contoh: S040" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Outlet <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="storeName" class="form-control" placeholder="Contoh: Mollyfantasy Aeon Mall Deltamas" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Name</label>
                        <input type="text" name="account_name" id="storeAccount" class="form-control" placeholder="Contoh: 543310504002001 Store M S040">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" id="storeAddress" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_active" id="storeActive" class="form-check-input" value="1" checked>
                        <label class="form-check-label">Status Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const modal = new bootstrap.Modal('#storeModal');
    const table = $('#tableStores').DataTable({
        processing: true, serverSide: true, ajax: '{{ route('stores.data') }}',
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'code' },
            { data: 'name' },
            { data: 'account_name', defaultContent: '-' },
            { data: 'items_count', className: 'text-center' },
            { data: 'users_count', className: 'text-center' },
            { data: 'status_badge', orderable: false, className: 'text-center' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' },
        ]
    });

    $('#btnAddStore').on('click', function () {
        $('#formStore')[0].reset();
        $('#storeId').val('');
        $('#storeModalTitle').text('Tambah Outlet');
        $('#formStore').data('url', '{{ route('stores.store') }}').data('method', 'POST');
    });

    $(document).on('click', '.btn-edit-store', function () {
        const btn = $(this);
        $('#storeId').val(btn.data('id'));
        $('#storeCode').val(btn.data('code'));
        $('#storeName').val(btn.data('name'));
        $('#storeAccount').val(btn.data('account'));
        $('#storeAddress').val(btn.data('address'));
        $('#storeActive').prop('checked', btn.data('active') == 1);
        $('#storeModalTitle').text('Edit Outlet');
        $('#formStore').data('url', '/stores/' + btn.data('id')).data('method', 'PUT');
        modal.show();
    });

    $('#formStore').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        $.ajax({
            url: form.data('url'), method: 'POST',
            data: form.serialize() + '&_method=' + form.data('method'),
            success: function (res) { modal.hide(); mfToast('success', res.message); table.ajax.reload(); },
            error: function (xhr) { mfToast('error', xhr.responseJSON?.message || 'Terjadi kesalahan.'); }
        });
    });

    $(document).on('click', '.btn-delete-store', function () {
        const id = $(this).data('id');
        mfConfirmDelete(id, function () {
            $.ajax({
                url: '/stores/' + id, method: 'DELETE',
                success: function (res) { mfToast('success', res.message); table.ajax.reload(); },
                error: function (xhr) { mfToast('error', xhr.responseJSON?.message || 'Gagal menghapus outlet.'); }
            });
        });
    });
});
</script>
@endpush
