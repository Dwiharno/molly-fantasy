@extends('layouts.app')

@section('title', 'Master Kategori')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Master Kategori</h4>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal" id="btnAddCategory">
        <i class="fa-solid fa-plus me-1"></i> Tambah Kategori
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table id="tableCategories" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Kategori</th>
                    <th>Kode</th>
                    <th>Deskripsi</th>
                    <th class="text-center">Sub Kategori</th>
                    <th class="text-center">Jumlah Item</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCategory">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="categoryId">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="categoryName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kode <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="categoryCode" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" id="categoryDescription" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_active" id="categoryActive" class="form-check-input" value="1" checked>
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
    const modal = new bootstrap.Modal('#categoryModal');
    const table = $('#tableCategories').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('categories.data') }}',
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'code' },
            { data: 'description', defaultContent: '-' },
            { data: 'sub_categories_count', className: 'text-center' },
            { data: 'items_count', className: 'text-center' },
            { data: 'status_badge', orderable: false, className: 'text-center' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' },
        ]
    });

    $('#btnAddCategory').on('click', function () {
        $('#formCategory')[0].reset();
        $('#categoryId').val('');
        $('#categoryModalTitle').text('Tambah Kategori');
        $('#formCategory').data('url', '{{ route('categories.store') }}').data('method', 'POST');
    });

    $(document).on('click', '.btn-edit-category', function () {
        const btn = $(this);
        $('#categoryId').val(btn.data('id'));
        $('#categoryName').val(btn.data('name'));
        $('#categoryCode').val(btn.data('code'));
        $('#categoryDescription').val(btn.data('description'));
        $('#categoryActive').prop('checked', btn.data('active') == 1);
        $('#categoryModalTitle').text('Edit Kategori');
        $('#formCategory').data('url', '/categories/' + btn.data('id')).data('method', 'PUT');
        modal.show();
    });

    $('#formCategory').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        $.ajax({
            url: form.data('url'),
            method: 'POST',
            data: form.serialize() + '&_method=' + form.data('method'),
            success: function (res) {
                modal.hide();
                mfToast('success', res.message);
                table.ajax.reload();
            },
            error: function (xhr) {
                mfToast('error', xhr.responseJSON?.message || 'Terjadi kesalahan.');
            }
        });
    });

    $(document).on('click', '.btn-delete-category', function () {
        const id = $(this).data('id');
        mfConfirmDelete(id, function () {
            $.ajax({
                url: '/categories/' + id,
                method: 'DELETE',
                success: function (res) {
                    mfToast('success', res.message);
                    table.ajax.reload();
                },
                error: function (xhr) {
                    mfToast('error', xhr.responseJSON?.message || 'Gagal menghapus kategori.');
                }
            });
        });
    });
});
</script>
@endpush
