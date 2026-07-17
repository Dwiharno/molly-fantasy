<div class="d-flex gap-1">
    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-category"
            data-id="{{ $category->id }}" data-name="{{ $category->name }}"
            data-code="{{ $category->code }}" data-description="{{ $category->description }}"
            data-active="{{ $category->is_active ? 1 : 0 }}" title="Edit">
        <i class="fa-solid fa-pen"></i>
    </button>
    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-category" data-id="{{ $category->id }}" title="Hapus">
        <i class="fa-solid fa-trash"></i>
    </button>
</div>
