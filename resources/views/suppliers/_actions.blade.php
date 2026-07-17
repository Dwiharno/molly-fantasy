<div class="d-flex gap-1">
    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-supplier"
            data-id="{{ $supplier->id }}" data-name="{{ $supplier->name }}"
            data-contact="{{ $supplier->contact_person }}" data-phone="{{ $supplier->phone }}"
            data-email="{{ $supplier->email }}" data-address="{{ $supplier->address }}"
            data-active="{{ $supplier->is_active ? 1 : 0 }}" title="Edit">
        <i class="fa-solid fa-pen"></i>
    </button>
    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-supplier" data-id="{{ $supplier->id }}" title="Hapus">
        <i class="fa-solid fa-trash"></i>
    </button>
</div>
