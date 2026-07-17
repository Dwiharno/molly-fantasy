@extends('layouts.app')

@section('title', 'Master User')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Master User</h4>
    @can('create', App\Models\User::class)
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Tambah User
        </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <table id="tableUsers" class="table table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>#</th><th>Nama</th><th>Email</th><th>Role</th>
                    <th class="text-center">Status</th><th>Login Terakhir</th><th class="text-center">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formResetPassword">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password — <span id="resetUserName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" minlength="8" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const table = $('#tableUsers').DataTable({
        processing: true, serverSide: true, ajax: '{{ route('users.data') }}',
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'email' },
            { data: 'role_label' },
            { data: 'status_badge', orderable: false, className: 'text-center' },
            { data: 'last_login' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' },
        ]
    });

    const resetModal = new bootstrap.Modal('#resetPasswordModal');
    let resetUserId = null;

    $(document).on('click', '.btn-reset-password', function () {
        resetUserId = $(this).data('id');
        $('#resetUserName').text($(this).data('name'));
        $('#formResetPassword')[0].reset();
        resetModal.show();
    });

    $('#formResetPassword').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '/users/' + resetUserId + '/reset-password',
            method: 'POST',
            data: $(this).serialize() + '&_method=POST',
            success: function (res) { resetModal.hide(); mfToast('success', res.message); },
            error: function (xhr) { mfToast('error', xhr.responseJSON?.message || 'Gagal mereset password.'); }
        });
    });

    $(document).on('click', '.btn-toggle-status', function () {
        const id = $(this).data('id');
        $.ajax({
            url: '/users/' + id + '/toggle-active',
            method: 'POST',
            success: function (res) { mfToast('success', res.message); table.ajax.reload(null, false); },
            error: function () { mfToast('error', 'Gagal mengubah status user.'); }
        });
    });

    $(document).on('click', '.btn-delete-user', function () {
        const id = $(this).data('id');
        mfConfirmDelete(id, function () {
            $.ajax({
                url: '/users/' + id, method: 'DELETE',
                success: function (res) { mfToast('success', res.message); table.ajax.reload(); },
                error: function (xhr) { mfToast('error', xhr.responseJSON?.message || 'Gagal menghapus user.'); }
            });
        });
    });

    @if(session('success'))
        mfToast('success', @json(session('success')));
    @endif
});
</script>
@endpush
