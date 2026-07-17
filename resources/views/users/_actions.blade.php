@php
    $authUser = auth()->user();
    $canUpdate = $authUser->can('update', $user);
    $canDelete = $authUser->can('delete', $user);
    $canReset = $authUser->can('resetPassword', $user);
@endphp
<div class="d-flex gap-1">
    @if($canUpdate)
        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary" title="Edit">
            <i class="fa-solid fa-pen"></i>
        </a>
        <button type="button" class="btn btn-sm btn-outline-warning btn-toggle-status" data-id="{{ $user->id }}" title="Aktif/Nonaktifkan">
            <i class="fa-solid fa-power-off"></i>
        </button>
    @endif
    @if($canReset)
        <button type="button" class="btn btn-sm btn-outline-info btn-reset-password" data-id="{{ $user->id }}" data-name="{{ $user->name }}" title="Reset Password">
            <i class="fa-solid fa-key"></i>
        </button>
    @endif
    @if($canDelete)
        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-user" data-id="{{ $user->id }}" title="Hapus">
            <i class="fa-solid fa-trash"></i>
        </button>
    @endif
</div>
