<div class="d-flex gap-1">
    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-store"
            data-id="{{ $store->id }}" data-code="{{ $store->code }}" data-name="{{ $store->name }}"
            data-account="{{ $store->account_name }}" data-address="{{ $store->address }}"
            data-active="{{ $store->is_active ? 1 : 0 }}" title="Edit">
        <i class="fa-solid fa-pen"></i>
    </button>
    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-store" data-id="{{ $store->id }}" title="Hapus">
        <i class="fa-solid fa-trash"></i>
    </button>
</div>
