@extends('layouts.app')

@section('title', 'Master Supplier')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Master Supplier</h4>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#supplierModal" id="btnAddSupplier">
        <i class="fa-solid fa-plus me-1"></i> Tambah Supplier
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table id="tableSuppliers" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>#</th><th>Nama Supplier</th><th>Kontak</th><th>Telepon</th>
                    <th class="text-center">Jumlah Item</th><th class="text-center">Status</th><th class="text-center">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formSupplier">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierModalTitle">Tambah Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="supplierId">
                    <div class="mb-3">
                        <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="supplierName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kontak Person</label>
                        <input type="text" name="contact_person" id="supplierContact" class="form-control">
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="phone" id="supplierPhone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="supplierEmail" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" id="supplierAddress" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_active" id="supplierActive" class="form-check-input" value="1" checked>
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
    const modal = new bootstrap.Modal('#supplierModal');
    const table = $('#tableSuppliers').DataTable({
        processing: true, serverSide: true, ajax: '{{ route('suppliers.data') }}',
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'contact_person', defaultContent: '-' },
            { data: 'phone', defaultContent: '-' },
            { data: 'items_count', className: 'text-center' },
            { data: 'status_badge', orderable: false, className: 'text-center' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' },
        ]
    });

    $('#btnAddSupplier').on('click', function () {
        $('#formSupplier')[0].reset();
        $('#supplierId').val('');
        $('#supplierModalTitle').text('Tambah Supplier');
        $('#formSupplier').data('url', '{{ route('suppliers.store') }}').data('method', 'POST');
    });

    $(document).on('click', '.btn-edit-supplier', function () {
        const btn = $(this);
        $('#supplierId').val(btn.data('id'));
        $('#supplierName').val(btn.data('name'));
        $('#supplierContact').val(btn.data('contact'));
        $('#supplierPhone').val(btn.data('phone'));
        $('#supplierEmail').val(btn.data('email'));
        $('#supplierAddress').val(btn.data('address'));
        $('#supplierActive').prop('checked', btn.data('active') == 1);
        $('#supplierModalTitle').text('Edit Supplier');
        $('#formSupplier').data('url', '/suppliers/' + btn.data('id')).data('method', 'PUT');
        modal.show();
    });

    $('#formSupplier').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        $.ajax({
            url: form.data('url'), method: 'POST',
            data: form.serialize() + '&_method=' + form.data('method'),
            success: function (res) { modal.hide(); mfToast('success', res.message); table.ajax.reload(); },
            error: function (xhr) { mfToast('error', xhr.responseJSON?.message || 'Terjadi kesalahan.'); }
        });
    });

    $(document).on('click', '.btn-delete-supplier', function () {
        const id = $(this).data('id');
        mfConfirmDelete(id, function () {
            $.ajax({
                url: '/suppliers/' + id, method: 'DELETE',
                success: function (res) { mfToast('success', res.message); table.ajax.reload(); },
                error: function (xhr) { mfToast('error', xhr.responseJSON?.message || 'Gagal menghapus supplier.'); }
            });
        });
    });
});
</script>
@endpush
