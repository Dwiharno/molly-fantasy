@extends('layouts.app')

@section('title', 'Master Brand')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Master Brand</h4>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#brandModal" id="btnAddBrand">
        <i class="fa-solid fa-plus me-1"></i> Tambah Brand
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table id="tableBrands" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>#</th><th>Nama Brand</th><th class="text-center">Jumlah Item</th>
                    <th class="text-center">Status</th><th class="text-center">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="brandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formBrand">
                <div class="modal-header">
                    <h5 class="modal-title" id="brandModalTitle">Tambah Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="brandId">
                    <div class="mb-3">
                        <label class="form-label">Nama Brand <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="brandName" class="form-control" required>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_active" id="brandActive" class="form-check-input" value="1" checked>
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
    const modal = new bootstrap.Modal('#brandModal');
    const table = $('#tableBrands').DataTable({
        processing: true, serverSide: true, ajax: '{{ route('brands.data') }}',
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'items_count', className: 'text-center' },
            { data: 'status_badge', orderable: false, className: 'text-center' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' },
        ]
    });

    $('#btnAddBrand').on('click', function () {
        $('#formBrand')[0].reset();
        $('#brandId').val('');
        $('#brandModalTitle').text('Tambah Brand');
        $('#formBrand').data('url', '{{ route('brands.store') }}').data('method', 'POST');
    });

    $(document).on('click', '.btn-edit-brand', function () {
        const btn = $(this);
        $('#brandId').val(btn.data('id'));
        $('#brandName').val(btn.data('name'));
        $('#brandActive').prop('checked', btn.data('active') == 1);
        $('#brandModalTitle').text('Edit Brand');
        $('#formBrand').data('url', '/brands/' + btn.data('id')).data('method', 'PUT');
        modal.show();
    });

    $('#formBrand').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        $.ajax({
            url: form.data('url'), method: 'POST',
            data: form.serialize() + '&_method=' + form.data('method'),
            success: function (res) { modal.hide(); mfToast('success', res.message); table.ajax.reload(); },
            error: function (xhr) { mfToast('error', xhr.responseJSON?.message || 'Terjadi kesalahan.'); }
        });
    });

    $(document).on('click', '.btn-delete-brand', function () {
        const id = $(this).data('id');
        mfConfirmDelete(id, function () {
            $.ajax({
                url: '/brands/' + id, method: 'DELETE',
                success: function (res) { mfToast('success', res.message); table.ajax.reload(); },
                error: function (xhr) { mfToast('error', xhr.responseJSON?.message || 'Gagal menghapus brand.'); }
            });
        });
    });
});
</script>
@endpush
