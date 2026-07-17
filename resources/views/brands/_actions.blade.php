<div class="d-flex gap-1">
    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-brand"
            data-id="{{ $brand->id }}" data-name="{{ $brand->name }}"
            data-active="{{ $brand->is_active ? 1 : 0 }}" title="Edit">
        <i class="fa-solid fa-pen"></i>
    </button>
    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-brand" data-id="{{ $brand->id }}" title="Hapus">
        <i class="fa-solid fa-trash"></i>
    </button>
</div>
